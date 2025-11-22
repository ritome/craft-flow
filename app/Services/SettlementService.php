<?php

declare(strict_types=1);

namespace App\Services;

use App\Exports\SettlementPdfExport;
use App\Imports\CustomerImport;
use App\Imports\SalesImport;
use App\Models\Settlement;
use App\Models\SettlementDetail;
use App\Support\ExcelColumnMapping;
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

            \Log::info('Data import completed', [
                'customers_count' => count($customers),
                'sales_count' => count($sales),
            ]);

            // 2. 委託先別精算データ自動変換 (Issue #13)
            $settlementData = $this->calculateSettlements($customers, $sales, $billingStartDate, $billingEndDate);

            \Log::info('Settlement calculation completed', [
                'settlement_data_count' => count($settlementData),
            ]);

            // データが0件の場合はエラー
            if (empty($settlementData)) {
                throw new \Exception(
                    '指定期間内に該当する売上データが見つかりませんでした。'.
                    '請求期間（'.$billingStartDate.' 〜 '.$billingEndDate.'）と'.
                    'アップロードファイルの内容を確認してください。'
                );
            }

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

        \Log::info('Customer file headers', [
            'headers' => $headers,
            'total_rows' => count($rows),
        ]);

        // ヘッダーを内部カラム名に変換（ExcelColumnMapping を使用）
        $normalizedHeaders = [];
        foreach ($headers as $index => $header) {
            $normalizedHeaders[$index] = ExcelColumnMapping::toInternalColumn(
                (string) $header,
                ExcelColumnMapping::CUSTOMER_COLUMNS
            );
        }

        // 必須列の存在チェック
        $requiredColumns = ExcelColumnMapping::getRequiredCustomerColumns();
        $missingColumns = ExcelColumnMapping::getMissingColumns($normalizedHeaders, $requiredColumns);

        if (! empty($missingColumns)) {
            throw new \Exception(
                '顧客マスタに必須列が不足しています: '.implode(', ', $missingColumns).
                "\n\nアップロードされた列: ".implode(', ', array_unique($normalizedHeaders))
            );
        }

        // ヘッダーでマッピング
        $customers = [];
        foreach ($rows as $row) {
            $customer = [];
            foreach ($normalizedHeaders as $index => $normalizedHeader) {
                $customer[$normalizedHeader] = $row[$index] ?? null;
            }

            // client_code が空の行はスキップ
            if (empty(trim((string) ($customer['client_code'] ?? '')))) {
                continue;
            }

            $customers[$customer['client_code']] = $customer;
        }

        \Log::info('Customer data import result', [
            'imported_count' => count($customers),
            'sample_codes' => array_slice(array_keys($customers), 0, 3),
        ]);

        return $customers;
    }

    /**
     * 売上データをインポート
     *
     * Issue #12: 精算用Excelデータアップロード機能
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

        \Log::info('Sales file headers', [
            'headers' => $headers,
            'total_rows' => count($rows),
        ]);

        // ヘッダーを内部カラム名に変換（ExcelColumnMapping を使用）
        $normalizedHeaders = [];
        foreach ($headers as $index => $header) {
            $normalizedHeaders[$index] = ExcelColumnMapping::toInternalColumn(
                (string) $header,
                ExcelColumnMapping::SALES_COLUMNS
            );
        }

        // 必須列の存在チェック
        $requiredColumns = ExcelColumnMapping::getRequiredSalesColumns();
        $missingColumns = ExcelColumnMapping::getMissingColumns($normalizedHeaders, $requiredColumns);

        if (! empty($missingColumns)) {
            throw new \Exception(
                '売上データに必須列が不足しています: '.implode(', ', $missingColumns).
                "\n\nアップロードされた列: ".implode(', ', array_unique($normalizedHeaders))
            );
        }

        // ヘッダーでマッピング
        $sales = [];
        foreach ($rows as $index => $row) {
            $saleData = [];
            foreach ($normalizedHeaders as $colIndex => $normalizedHeader) {
                $value = $row[$colIndex] ?? null;

                // 売上日をフォーマット変換（Excel数値日付の場合）
                if ($normalizedHeader === 'sale_date' && is_numeric($value)) {
                    $value = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
                }

                $saleData[$normalizedHeader] = $value;
            }

            // client_code が空の行はスキップ
            if (empty(trim((string) ($saleData['client_code'] ?? '')))) {
                continue;
            }

            // 最初の数件をログに記録（デバッグ用）
            if ($index < 3) {
                \Log::info('Sample sales data row', [
                    'row_index' => $index,
                    'data' => $saleData,
                ]);
            }

            $sales[] = $saleData;
        }

        \Log::info('Sales data import result', [
            'imported_count' => count($sales),
            'sample_client_codes' => array_slice(array_unique(array_column($sales, 'client_code')), 0, 5),
        ]);

        return $sales;
    }

    /**
     * 委託先別精算データ自動変換
     *
     * Issue #13: 委託先別精算データ自動変換機能
     */
    private function calculateSettlements(
        array $customers,
        array $sales,
        string $billingStartDate,
        string $billingEndDate
    ): array {
        $settlementData = [];
        $skippedByDate = 0;
        $skippedByMissingClient = 0;

        // 期間内の売上を委託先ごとに集計
        foreach ($sales as $sale) {
            $saleDate = $sale['sale_date'] ?? '';
            $clientCode = $sale['client_code'] ?? '';

            // 期間チェック
            if ($saleDate < $billingStartDate || $saleDate > $billingEndDate) {
                $skippedByDate++;

                continue;
            }

            // 委託先が存在しない場合はスキップ
            if (! isset($customers[$clientCode])) {
                \Log::warning("委託先コード {$clientCode} が顧客マスタに存在しません。");
                $skippedByMissingClient++;

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

            // 手数料率は売上データか顧客マスタから取得（優先順位: 売上データ > 顧客マスタ > デフォルト）
            $commissionRate = (float) ($sale['commission_rate'] ?? $customer['commission_rate'] ?? config('settlement.calculation.commission_rate', 20));
            $commissionAmount = $amount * ($commissionRate / 100);
            $paymentAmount = $amount - $commissionAmount;

            // 集計
            $settlementData[$clientCode]['sales_amount'] += $amount;
            $settlementData[$clientCode]['commission_amount'] += $commissionAmount;
            $settlementData[$clientCode]['payment_amount'] += $paymentAmount;
            $settlementData[$clientCode]['sales_count']++;

            // 売上明細に手数料率を追加して保存（個別レコードとして）
            $sale['commission_rate'] = $commissionRate;
            $settlementData[$clientCode]['sales_details'][] = $sale;
        }

        \Log::info('Settlement calculation stats', [
            'total_sales' => count($sales),
            'skipped_by_date' => $skippedByDate,
            'skipped_by_missing_client' => $skippedByMissingClient,
            'clients_with_data' => count($settlementData),
        ]);

        // 各委託先の詳細ログ
        foreach ($settlementData as $clientCode => $data) {
            \Log::info("Client settlement data: {$clientCode}", [
                'client_name' => $data['client_name'],
                'sales_count' => $data['sales_count'],
                'sales_details_count' => count($data['sales_details']),
                'sales_amount' => $data['sales_amount'],
                'commission_amount' => $data['commission_amount'],
            ]);
        }

        return $settlementData;
    }

    /**
     * 精算履歴を DB に保存
     *
     * Issue #16: 精算書発行履歴保存機能
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
     * Excel/PDF ファイル生成（テンプレートベース）
     *
     * Issue #14: 月次委託精算書一括生成機能
     * Issue #15: 精算書ファイル（PDF/Excel）ダウンロード機能
     *
     * 完成例のテンプレートを使用して、委託先ごとにExcelファイルを生成
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
        \Log::info('Settlement data for file generation (template-based)', [
            'settlement_id' => $settlement->id,
            'client_count' => count($settlementData),
            'first_client' => ! empty($settlementData) ? array_keys($settlementData)[0] : 'none',
        ]);

        // リレーションをリフレッシュ
        $settlement->load('details');

        // Excel ファイル生成（テンプレートベース）
        $excelPath = $this->generateExcelFiles($settlement, $settlementData, $dateStr, $storageDir);

        // PDF ファイル生成（既存の方式を維持）
        $pdfPath = $this->generatePdfFile($settlement, $settlementData, $dateStr);

        // ファイルパスを更新
        $settlement->update([
            'excel_path' => $excelPath,
            'pdf_path' => $pdfPath,
        ]);

        \Log::info('File paths updated in database', [
            'settlement_id' => $settlement->id,
            'excel_path' => $excelPath,
            'pdf_path' => $pdfPath,
        ]);
    }

    /**
     * テンプレートベースでExcelファイルを生成
     *
     * @return string 保存したファイルパス
     */
    private function generateExcelFiles(
        Settlement $settlement,
        array $settlementData,
        string $dateStr,
        string $storageDir
    ): string {
        try {
            // テンプレートサービスをインスタンス化
            $templateService = new \App\Services\Settlement\SettlementTemplateService;

            // 委託先ごとにExcelファイルを生成
            $excelFiles = [];

            foreach ($settlementData as $clientCode => $clientData) {
                \Log::info('Generating Excel for client', [
                    'client_code' => $clientCode,
                    'client_name' => $clientData['client_name'] ?? 'unknown',
                ]);

                // テンプレートを読み込む
                $spreadsheet = $templateService->loadTemplate();

                // データを書き込む
                $templateService->fillTemplate($spreadsheet, $settlement, $clientData);

                // ファイル名を生成（委託先名_日付_精算書.xlsx）
                $clientName = $this->sanitizeFileName($clientData['client_name'] ?? $clientCode);
                $fileName = "{$clientName}_{$dateStr}_精算書.xlsx";
                $filePath = $storageDir.'/'.$fileName;

                // ファイルを保存
                $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->save($filePath);

                $excelFiles[] = $filePath;

                \Log::info('Excel file generated successfully', [
                    'client_code' => $clientCode,
                    'file' => $fileName,
                ]);
            }

            // 複数ファイルをZIPにまとめる
            if (count($excelFiles) > 1) {
                $zipFileName = "settlement_{$dateStr}_{$settlement->id}.zip";
                $zipPath = $storageDir.'/'.$zipFileName;

                $this->createZipArchive($excelFiles, $zipPath);

                // 個別ファイルを削除
                foreach ($excelFiles as $file) {
                    @unlink($file);
                }

                // Storageに登録（Storage APIで正しく認識されるように）
                $storagePath = "settlements/{$zipFileName}";
                $zipContent = file_get_contents($zipPath);
                Storage::disk('local')->put($storagePath, $zipContent);
                
                // 元のZIPファイルを削除（Storageで管理されるようになったため）
                @unlink($zipPath);

                \Log::info('Created ZIP archive and registered to Storage', [
                    'file' => $zipFileName,
                    'client_count' => count($excelFiles),
                    'storage_path' => $storagePath,
                ]);

                return $storagePath;
            } elseif (count($excelFiles) === 1) {
                // 1ファイルの場合はStorageに登録
                $fileName = basename($excelFiles[0]);
                $storagePath = "settlements/{$fileName}";
                $excelContent = file_get_contents($excelFiles[0]);
                Storage::disk('local')->put($storagePath, $excelContent);
                
                // 元のファイルを削除
                @unlink($excelFiles[0]);
                
                \Log::info('Registered single Excel file to Storage', [
                    'file' => $fileName,
                    'storage_path' => $storagePath,
                ]);
                
                return $storagePath;
            } else {
                throw new \Exception('生成するExcelファイルがありません');
            }

        } catch (\Exception $e) {
            \Log::error("Excel generation error: {$e->getMessage()}");
            \Log::error('Stack trace: '.$e->getTraceAsString());
            throw new \Exception("Excelファイルの生成に失敗しました: {$e->getMessage()}");
        }
    }

    /**
     * PDFファイルを生成
     *
     * @return string 保存したファイルパス
     */
    private function generatePdfFile(
        Settlement $settlement,
        array $settlementData,
        string $dateStr
    ): string {
        try {
            // PDF ファイル生成（既存の方式）
            $pdfPath = "settlements/settlement_{$dateStr}_{$settlement->id}.pdf";
            \Log::info("Generating PDF file: {$pdfPath}");

            $pdfExport = new SettlementPdfExport($settlement, $settlementData);
            $pdfContent = $pdfExport->generate();
            Storage::disk('local')->put($pdfPath, $pdfContent);

            \Log::info("PDF file generated successfully: {$pdfPath}");

            return $pdfPath;
        } catch (\Exception $e) {
            \Log::error("PDF generation error: {$e->getMessage()}");
            \Log::error('Stack trace: '.$e->getTraceAsString());
            throw new \Exception("PDFファイルの生成に失敗しました: {$e->getMessage()}");
        }
    }

    /**
     * ZIPアーカイブを作成
     *
     * @param  array  $files  ZIPに含めるファイルパスの配列
     * @param  string  $zipPath  作成するZIPファイルのパス
     */
    private function createZipArchive(array $files, string $zipPath): void
    {
        $zip = new \ZipArchive;

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("ZIPファイルの作成に失敗しました: {$zipPath}");
        }

        foreach ($files as $file) {
            if (file_exists($file)) {
                $zip->addFile($file, basename($file));
            } else {
                \Log::warning('File not found for ZIP', ['file' => $file]);
            }
        }

        $zip->close();

        \Log::info('ZIP archive created', [
            'path' => $zipPath,
            'file_count' => count($files),
        ]);
    }

    /**
     * ファイル名として使用できるように文字列をサニタイズ
     */
    private function sanitizeFileName(string $filename): string
    {
        // ファイル名に使用できない文字を置換
        $invalid = ['/', '\\', ':', '*', '?', '"', '<', '>', '|'];
        $sanitized = str_replace($invalid, '_', $filename);

        // 最大長を制限（拡張子を除いて50文字）
        return mb_substr($sanitized, 0, 50);
    }

    /**
     * 精算履歴を削除（ファイルも削除）
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
     */
    private function generateSettlementNumber(string $billingStartDate): string
    {
        $date = \Carbon\Carbon::parse($billingStartDate);
        $yearMonth = $date->format('Y-m');

        // 重複しない番号を生成するまでリトライ（最大10回）
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            // その年月の精算書数を取得して連番を決定
            $count = Settlement::whereYear('billing_start_date', $date->year)
                ->whereMonth('billing_start_date', $date->month)
                ->lockForUpdate() // 行ロックを使用
                ->count();

            $sequenceNumber = $count + $attempt;
            $settlementNumber = sprintf('%s-C%03d', $yearMonth, $sequenceNumber);

            // 既に存在するかチェック
            $exists = Settlement::where('settlement_number', $settlementNumber)->exists();

            if (! $exists) {
                \Log::info('Settlement number generated', [
                    'number' => $settlementNumber,
                    'attempt' => $attempt,
                ]);

                return $settlementNumber;
            }

            \Log::warning('Settlement number collision detected, retrying', [
                'number' => $settlementNumber,
                'attempt' => $attempt,
            ]);
        }

        // 10回試してダメなら、タイムスタンプを使った一意な番号を生成
        $fallbackNumber = sprintf('%s-C%03d-%s', $yearMonth, 999, substr(md5(microtime()), 0, 6));
        \Log::warning('Using fallback settlement number', ['number' => $fallbackNumber]);

        return $fallbackNumber;
    }

    /**
     * 振込予定日を計算
     *
     * 精算期間終了日 + 設定された日数
     */
    private function calculatePaymentDate(string $billingEndDate): string
    {
        $endDate = \Carbon\Carbon::parse($billingEndDate);
        $daysAfter = config('settlement.payment.days_after_period_end', 40);

        return $endDate->addDays($daysAfter)->format('Y-m-d');
    }
}
