<?php

declare(strict_types=1);

namespace App\Services\Settlement;

use App\Models\Settlement;
use App\Support\Excel\SettlementTemplateCells as Cells;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * 精算書テンプレート処理サービス
 *
 * テンプレートExcelファイルを読み込み、データを埋め込む
 */
class SettlementTemplateService
{
    /**
     * テンプレートファイルを読み込む
     *
     * @throws \Exception
     */
    public function loadTemplate(): Spreadsheet
    {
        $templatePath = base_path(Cells::TEMPLATE_PATH);

        if (! file_exists($templatePath)) {
            throw new \Exception(
                'テンプレートファイルが見つかりません: '.Cells::TEMPLATE_PATH.
                "\n\n完成例の精算書Excelを resources/excel/settlement_template.xlsx として配置してください。"
            );
        }

        try {
            $spreadsheet = IOFactory::load($templatePath);
            \Log::info('Template loaded successfully', ['path' => $templatePath]);

            return $spreadsheet;
        } catch (\Exception $e) {
            \Log::error('Failed to load template', [
                'path' => $templatePath,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('テンプレートファイルの読み込みに失敗しました: '.$e->getMessage());
        }
    }

    /**
     * 精算データをテンプレートに書き込む
     */
    public function fillTemplate(
        Spreadsheet $spreadsheet,
        Settlement $settlement,
        array $clientData
    ): void {
        $sheet = $spreadsheet->getActiveSheet();

        // Settlementインスタンスを保持
        $this->settlement = $settlement;

        // 各セクションにデータを書き込み
        $this->fillHeader($sheet, $settlement);
        $this->fillClientInfo($sheet, $clientData, $settlement);
        $this->fillAmountBoxes($sheet, $clientData);
        $detailEndRow = $this->fillDetails($sheet, $clientData['sales_details'] ?? []);
        $this->fillTotals($sheet, $clientData, $detailEndRow);
        $this->fillBankInfo($sheet, $clientData, $detailEndRow);

        \Log::info('Template filled successfully', [
            'settlement_id' => $settlement->id,
            'client_code' => $clientData['client_code'] ?? 'unknown',
        ]);
    }

    /**
     * ヘッダー情報を書き込む
     */
    private function fillHeader(Worksheet $sheet, Settlement $settlement): void
    {
        // 精算番号
        $sheet->setCellValue(Cells::SETTLEMENT_NUMBER, $settlement->settlement_number);

        // 発行日
        $sheet->setCellValue(Cells::ISSUE_DATE, $settlement->created_at->format('Y年n月j日'));
    }

    /**
     * 委託先情報を書き込む
     */
    private function fillClientInfo(
        Worksheet $sheet,
        array $clientData,
        Settlement $settlement
    ): void {
        // 請求期間
        $billingPeriod = '【精算期間】  '.
            $settlement->billing_start_date->format('Y年n月j日').
            ' 〜 '.
            $settlement->billing_end_date->format('Y年n月j日');
        $sheet->setCellValue(Cells::BILLING_PERIOD, $billingPeriod);

        // 委託先名
        $clientName = $clientData['client_name'].'  様';
        $sheet->setCellValue(Cells::CLIENT_NAME, $clientName);

        // 委託先郵便番号
        if (! empty($clientData['postal_code'])) {
            $sheet->setCellValue(Cells::CLIENT_POSTAL_CODE, $clientData['postal_code']);
        }

        // 委託先住所
        if (! empty($clientData['address'])) {
            $sheet->setCellValue(Cells::CLIENT_ADDRESS, $clientData['address']);
        }
    }

    /**
     * 金額ボックスを書き込む
     */
    private function fillAmountBoxes(Worksheet $sheet, array $clientData): void
    {
        // 計算
        $salesTotal = (float) ($clientData['sales_amount'] ?? 0);
        $commissionRate = config('settlement.calculation.commission_rate', 20) / 100;
        $taxRate = config('settlement.calculation.tax_rate', 10) / 100;
        $transferFee = config('settlement.calculation.transfer_fee', 440);

        $commission = round($salesTotal * $commissionRate);
        $amountAfterCommission = $salesTotal - $commission;
        $tax = round($amountAfterCommission * $taxRate);
        $paymentAmount = $amountAfterCommission + $tax - $transferFee;

        // お支払金額（黄色ボックス、右上）
        $sheet->setCellValue(
            Cells::PAYMENT_AMOUNT,
            '¥'.number_format($paymentAmount, 0)
        );
    }

    /**
     * 商品明細を書き込む（動的行追加）
     *
     * @return int 明細の最終行番号
     */
    private function fillDetails(Worksheet $sheet, array $salesDetails): int
    {
        $currentRow = Cells::DETAIL_START_ROW;

        if (empty($salesDetails)) {
            \Log::warning('No sales details to fill');

            return $currentRow - 1;  // ヘッダー行を返す
        }

        foreach ($salesDetails as $index => $detail) {
            // 2行目以降は行を挿入
            if ($index > 0) {
                $sheet->insertNewRowBefore($currentRow, 1);
                // 前の行のスタイルをコピー（罫線など）
                $this->copyRowStyle($sheet, $currentRow - 1, $currentRow);
            }

            $cells = Cells::detailCells($currentRow);

            // 各列にデータを書き込み
            $sheet->setCellValue($cells['code'], $detail['product_code'] ?? '');
            $sheet->setCellValue($cells['name'], $detail['product_name'] ?? '');
            $sheet->setCellValue($cells['price'], (float) ($detail['unit_price'] ?? 0));
            $sheet->setCellValue($cells['qty'], (int) ($detail['quantity'] ?? 0));
            $sheet->setCellValue($cells['amount'], (float) ($detail['amount'] ?? 0));

            $currentRow++;
        }

        \Log::info('Details filled', [
            'count' => count($salesDetails),
            'last_row' => $currentRow - 1,
        ]);

        return $currentRow - 1;  // 最終行番号を返す
    }

    /**
     * 集計行を書き込む
     */
    private function fillTotals(Worksheet $sheet, array $clientData, int $detailEndRow): void
    {
        // 計算
        $salesTotal = (float) ($clientData['sales_amount'] ?? 0);
        $commissionRate = config('settlement.calculation.commission_rate', 20) / 100;
        $taxRate = config('settlement.calculation.tax_rate', 10) / 100;
        $transferFee = config('settlement.calculation.transfer_fee', 440);

        $commission = round($salesTotal * $commissionRate);
        $amountAfterCommission = $salesTotal - $commission;
        $tax = round($amountAfterCommission * $taxRate);
        $paymentAmount = $amountAfterCommission + $tax - $transferFee;

        $cells = Cells::totalCells($detailEndRow);

        // 各集計値を書き込み
        $sheet->setCellValue($cells['subtotal'], $salesTotal);
        $sheet->setCellValue($cells['commission'], -$commission);
        $sheet->setCellValue($cells['tax'], $tax);
        $sheet->setCellValue($cells['transfer_fee'], -$transferFee);
        $sheet->setCellValue($cells['payment'], $paymentAmount);

        \Log::info('Totals filled', [
            'subtotal_cell' => $cells['subtotal'],
            'payment_amount' => $paymentAmount,
        ]);
    }

    /**
     * 振込先情報を書き込む
     */
    private function fillBankInfo(Worksheet $sheet, array $clientData, int $detailEndRow): void
    {
        $cells = Cells::totalCells($detailEndRow);
        $paymentRowNum = (int) filter_var($cells['payment'], FILTER_SANITIZE_NUMBER_INT);

        $bankCells = Cells::bankInfoCells($paymentRowNum);

        // お振込予定日
        $paymentDate = '【お振込予定日】  '.
            \Carbon\Carbon::parse($this->settlement->payment_date ?? now()->addDays(40))->format('Y年n月j日');
        $sheet->setCellValue($bankCells['payment_date'], $paymentDate);

        // 振込先ラベル
        $sheet->setCellValue($bankCells['bank_label'], '【振込先】');

        // 銀行情報
        $bankInfo = ($clientData['bank_name'] ?? '').'  '.
                    ($clientData['branch_name'] ?? '').'  '.
                    ($clientData['account_type'] ?? '').'  '.
                    ($clientData['account_number'] ?? '');
        $sheet->setCellValue($bankCells['bank_info'], $bankInfo);

        // 口座名義
        if (! empty($clientData['account_name'])) {
            $accountNameCell = Cells::cell('A', $paymentRowNum + Cells::ACCOUNT_INFO_ROW_OFFSET + 1);
            $sheet->setCellValue($accountNameCell, '口座名義: '.$clientData['account_name']);
        }
    }

    /**
     * 行のスタイルをコピー
     */
    private function copyRowStyle(Worksheet $sheet, int $sourceRow, int $targetRow): void
    {
        try {
            // A列からF列までのスタイルをコピー
            foreach (range('A', 'F') as $col) {
                $sourceCell = $col.$sourceRow;
                $targetCell = $col.$targetRow;

                // スタイルをコピー
                $sheet->duplicateStyle(
                    $sheet->getStyle($sourceCell),
                    $targetCell
                );
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to copy row style', [
                'source' => $sourceRow,
                'target' => $targetRow,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Settlement インスタンスを保持（振込先情報で使用）
     */
    private ?Settlement $settlement = null;
}
