<?php

declare(strict_types=1);

namespace App\Services;

use App\Exports\SettlementExcelExport;
use App\Exports\SettlementPdfExport;
use App\Imports\CustomerImport;
use App\Imports\SalesImport;
use App\Models\Settlement;
use App\Models\SettlementDetail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * 委託精算書サービス
 * 
 * Issue #12〜#17 のビジネスロジックを実装
 */
class SettlementService
{
    /**
     * 精算書一括生成
     * 
     * 処理フロー：
     * 1. Excel アップロード＆データ読み込み (#12)
     * 2. 委託先別精算データ自動変換 (#13)
     * 3. Excel/PDF ファイル生成 (#14)
     * 4. ストレージ保存 (#15)
     * 5. DB 履歴保存 (#16)
     * 
     * @param  string  $billingStartDate
     * @param  string  $billingEndDate
     * @param  UploadedFile  $customerFile
     * @param  UploadedFile  $salesFile
     * @return Settlement
     *
     * @throws \Exception
     */
    public function generateSettlements(
        string $billingStartDate,
        string $billingEndDate,
        UploadedFile $customerFile,
        UploadedFile $salesFile
    ): Settlement {
        return DB::transaction(function () use ($billingStartDate, $billingEndDate, $customerFile, $salesFile) {
            // 1. Excel データ読み込み (Issue #12)
            $customers = $this->importCustomers($customerFile);
            $sales = $this->importSales($salesFile);

            // 2. 委託先別精算データ自動変換 (Issue #13)
            $settlementData = $this->calculateSettlements($customers, $sales, $billingStartDate, $billingEndDate);

            // 3. 精算履歴を DB に保存 (Issue #16)
            $settlement = $this->saveSettlement($billingStartDate, $billingEndDate, $settlementData);

            // 4. Excel/PDF ファイル生成 (Issue #14, #15)
            $this->generateFiles($settlement, $settlementData);

            return $settlement;
        });
    }

    /**
     * 顧客マスタをインポート
     * 
     * Issue #12: 精算用Excelデータアップロード機能
     * 
     * @param  UploadedFile  $file
     * @return array
     */
    private function importCustomers(UploadedFile $file): array
    {
        $importedData = Excel::toArray(new CustomerImport, $file);

        if (empty($importedData) || empty($importedData[0])) {
            throw new \Exception('顧客マスタにデータが見つかりません。');
        }

        $sheet = $importedData[0];
        $headers = $sheet[0] ?? [];
        $rows = array_slice($sheet, 1);

        // ヘッダーでマッピング
        $customers = [];
        foreach ($rows as $row) {
            $customer = [];
            foreach ($headers as $index => $header) {
                $customer[$header] = $row[$index] ?? null;
            }

            // client_code が空の行はスキップ
            if (empty(trim((string) ($customer['client_code'] ?? '')))) {
                continue;
            }

            $customers[$customer['client_code']] = $customer;
        }

        return $customers;
    }

    /**
     * 売上データをインポート
     * 
     * Issue #12: 精算用Excelデータアップロード機能
     * 
     * @param  UploadedFile  $file
     * @return array
     */
    private function importSales(UploadedFile $file): array
    {
        $importedData = Excel::toArray(new SalesImport, $file);

        if (empty($importedData) || empty($importedData[0])) {
            throw new \Exception('売上データにデータが見つかりません。');
        }

        $sheet = $importedData[0];
        $headers = $sheet[0] ?? [];
        $rows = array_slice($sheet, 1);

        // ヘッダーでマッピング
        $sales = [];
        foreach ($rows as $row) {
            $saleData = [];
            foreach ($headers as $index => $header) {
                $saleData[$header] = $row[$index] ?? null;
            }

            // client_code が空の行はスキップ
            if (empty(trim((string) ($saleData['client_code'] ?? '')))) {
                continue;
            }

            $sales[] = $saleData;
        }

        return $sales;
    }

    /**
     * 委託先別精算データ自動変換
     * 
     * Issue #13: 委託先別精算データ自動変換機能
     * 
     * @param  array  $customers
     * @param  array  $sales
     * @param  string  $billingStartDate
     * @param  string  $billingEndDate
     * @return array
     */
    private function calculateSettlements(
        array $customers,
        array $sales,
        string $billingStartDate,
        string $billingEndDate
    ): array {
        $settlementData = [];

        // 期間内の売上を委託先ごとに集計
        foreach ($sales as $sale) {
            $saleDate = $sale['sale_date'] ?? '';
            $clientCode = $sale['client_code'] ?? '';

            // 期間チェック
            if ($saleDate < $billingStartDate || $saleDate > $billingEndDate) {
                continue;
            }

            // 委託先が存在しない場合はスキップ
            if (! isset($customers[$clientCode])) {
                \Log::warning("委託先コード {$clientCode} が顧客マスタに存在しません。");

                continue;
            }

            // 初回の場合、委託先情報を初期化
            if (! isset($settlementData[$clientCode])) {
                $customer = $customers[$clientCode];
                $settlementData[$clientCode] = [
                    'client_code' => $clientCode,
                    'client_name' => $customer['client_name'] ?? '',
                    'postal_code' => $customer['postal_code'] ?? '',
                    'address' => $customer['address'] ?? '',
                    'bank_name' => $customer['bank_name'] ?? '',
                    'branch_name' => $customer['branch_name'] ?? '',
                    'account_type' => $customer['account_type'] ?? '',
                    'account_number' => $customer['account_number'] ?? '',
                    'account_name' => $customer['account_name'] ?? '',
                    'sales_amount' => 0,
                    'commission_amount' => 0,
                    'payment_amount' => 0,
                    'sales_count' => 0,
                    'sales_details' => [],
                ];
            }

            // 金額計算
            $amount = (float) ($sale['amount'] ?? 0);
            $commissionRate = (float) ($sale['commission_rate'] ?? 0);
            $commissionAmount = $amount * ($commissionRate / 100);
            $paymentAmount = $amount - $commissionAmount;

            // 集計
            $settlementData[$clientCode]['sales_amount'] += $amount;
            $settlementData[$clientCode]['commission_amount'] += $commissionAmount;
            $settlementData[$clientCode]['payment_amount'] += $paymentAmount;
            $settlementData[$clientCode]['sales_count']++;
            $settlementData[$clientCode]['sales_details'][] = $sale;
        }

        return $settlementData;
    }

    /**
     * 精算履歴を DB に保存
     * 
     * Issue #16: 精算書発行履歴保存機能
     * 
     * @param  string  $billingStartDate
     * @param  string  $billingEndDate
     * @param  array  $settlementData
     * @return Settlement
     */
    private function saveSettlement(
        string $billingStartDate,
        string $billingEndDate,
        array $settlementData
    ): Settlement {
        // 統計情報を計算
        $totalSalesAmount = 0;
        $totalCommission = 0;
        $totalPaymentAmount = 0;

        foreach ($settlementData as $data) {
            $totalSalesAmount += $data['sales_amount'];
            $totalCommission += $data['commission_amount'];
            $totalPaymentAmount += $data['payment_amount'];
        }

        // 精算番号を生成
        $settlementNumber = $this->generateSettlementNumber($billingStartDate);
        
        // 振込予定日を計算
        $paymentDate = $this->calculatePaymentDate($billingEndDate);

        // 精算履歴を作成
        $settlement = Settlement::create([
            'settlement_number' => $settlementNumber,
            'billing_start_date' => $billingStartDate,
            'billing_end_date' => $billingEndDate,
            'payment_date' => $paymentDate,
            'client_count' => count($settlementData),
            'total_sales_amount' => $totalSalesAmount,
            'total_commission' => $totalCommission,
            'total_payment_amount' => $totalPaymentAmount,
        ]);

        // 精算明細を保存
        foreach ($settlementData as $data) {
            SettlementDetail::create([
                'settlement_id' => $settlement->id,
                'client_code' => $data['client_code'],
                'client_name' => $data['client_name'],
                'postal_code' => $data['postal_code'],
                'address' => $data['address'],
                'bank_name' => $data['bank_name'],
                'branch_name' => $data['branch_name'],
                'account_type' => $data['account_type'],
                'account_number' => $data['account_number'],
                'account_name' => $data['account_name'],
                'sales_amount' => $data['sales_amount'],
                'commission_amount' => $data['commission_amount'],
                'payment_amount' => $data['payment_amount'],
                'sales_count' => $data['sales_count'],
                'sales_details' => $data['sales_details'] ?? [],
            ]);
        }

        return $settlement;
    }

    /**
     * Excel/PDF ファイル生成
     * 
     * Issue #14: 月次委託精算書一括生成機能
     * Issue #15: 精算書ファイル（PDF/Excel）ダウンロード機能
     * 
     * @param  Settlement  $settlement
     * @param  array  $settlementData
     * @return void
     */
    private function generateFiles(Settlement $settlement, array $settlementData): void
    {
        $dateStr = $settlement->billing_start_date->format('Ymd').'_'.$settlement->billing_end_date->format('Ymd');

        // settlements ディレクトリの存在確認と作成
        $storageDir = storage_path('app/settlements');
        if (! file_exists($storageDir)) {
            mkdir($storageDir, 0755, true);
            \Log::info("Created settlements directory: {$storageDir}");
        }

        // データのデバッグログ
        \Log::info("Settlement data for file generation", [
            'settlement_id' => $settlement->id,
            'client_count' => count($settlementData),
            'first_client' => !empty($settlementData) ? array_keys($settlementData)[0] : 'none',
        ]);

        // リレーションをリフレッシュ
        $settlement->load('details');

        try {
            // Excel ファイル生成
            $excelPath = "settlements/settlement_{$dateStr}_{$settlement->id}.xlsx";
            \Log::info("Generating Excel file: {$excelPath}");
            
            Excel::store(
                new SettlementExcelExport($settlement, $settlementData),
                $excelPath,
                'local'
            );
            
            \Log::info("Excel file generated successfully: {$excelPath}");
        } catch (\Exception $e) {
            \Log::error("Excel generation error: {$e->getMessage()}");
            \Log::error("Stack trace: " . $e->getTraceAsString());
            throw new \Exception("Excel ファイルの生成に失敗しました: {$e->getMessage()}");
        }

        try {
            // PDF ファイル生成
            $pdfPath = "settlements/settlement_{$dateStr}_{$settlement->id}.pdf";
            \Log::info("Generating PDF file: {$pdfPath}");
            
            $pdfExport = new SettlementPdfExport($settlement, $settlementData);
            $pdfContent = $pdfExport->generate();
            Storage::disk('local')->put($pdfPath, $pdfContent);
            
            \Log::info("PDF file generated successfully: {$pdfPath}");
        } catch (\Exception $e) {
            \Log::error("PDF generation error: {$e->getMessage()}");
            \Log::error("Stack trace: " . $e->getTraceAsString());
            throw new \Exception("PDF ファイルの生成に失敗しました: {$e->getMessage()}");
        }

        // ファイルパスを更新
        $settlement->update([
            'excel_path' => $excelPath,
            'pdf_path' => $pdfPath,
        ]);
        
        \Log::info("File paths updated in database", [
            'settlement_id' => $settlement->id,
            'excel_path' => $excelPath,
            'pdf_path' => $pdfPath,
        ]);
    }

    /**
     * 精算履歴を削除（ファイルも削除）
     * 
     * @param  Settlement  $settlement
     * @return void
     */
    public function deleteSettlement(Settlement $settlement): void
    {
        // ファイルを削除
        if ($settlement->hasExcelFile()) {
            Storage::disk('local')->delete($settlement->excel_path);
        }

        if ($settlement->hasPdfFile()) {
            Storage::disk('local')->delete($settlement->pdf_path);
        }

        // DB レコード削除（カスケードで details も削除される）
        $settlement->delete();
    }

    /**
     * 精算番号を生成
     * 
     * フォーマット: YYYY-MM-C### （例: 2025-10-C001）
     * その年月の連番を自動採番
     * 
     * @param  string  $billingStartDate
     * @return string
     */
    private function generateSettlementNumber(string $billingStartDate): string
    {
        $date = \Carbon\Carbon::parse($billingStartDate);
        $yearMonth = $date->format('Y-m');
        
        // その年月の精算書数を取得して連番を決定
        $count = Settlement::whereYear('billing_start_date', $date->year)
            ->whereMonth('billing_start_date', $date->month)
            ->count();
        
        $sequenceNumber = $count + 1;
        
        return sprintf('%s-C%03d', $yearMonth, $sequenceNumber);
    }

    /**
     * 振込予定日を計算
     * 
     * 精算期間終了日 + 設定された日数
     * 
     * @param  string  $billingEndDate
     * @return string
     */
    private function calculatePaymentDate(string $billingEndDate): string
    {
        $endDate = \Carbon\Carbon::parse($billingEndDate);
        $daysAfter = config('settlement.payment.days_after_period_end', 40);
        
        return $endDate->addDays($daysAfter)->format('Y-m-d');
    }
}

