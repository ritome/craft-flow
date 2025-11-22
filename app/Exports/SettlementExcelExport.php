<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Settlement;
use App\Services\Settlement\SettlementTemplateService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * 精算書 Excel エクスポート（テンプレートベース）
 *
 * Issue #14: 月次委託精算書一括生成機能
 *
 * テンプレートファイルを読み込み、データを埋め込む方式に変更
 * 1委託先 = 1シート の構成
 */
class SettlementExcelExport implements WithMultipleSheets
{
    /**
     * テンプレートサービス
     */
    private SettlementTemplateService $templateService;

    /**
     * コンストラクタ
     */
    public function __construct(
        private readonly Settlement $settlement,
        private readonly array $settlementData
    ) {
        $this->templateService = new SettlementTemplateService;
    }

    /**
     * 複数シートを生成
     */
    public function sheets(): array
    {
        $sheets = [];

        // settlementData が空の場合、DBから取得
        $data = $this->settlementData;
        if (empty($data)) {
            $data = $this->convertDetailsToArray();
        }

        \Log::info('Excel export sheets generation (template-based)', [
            'settlement_id' => $this->settlement->id,
            'data_count' => count($data),
            'data_keys' => array_keys($data),
        ]);

        // データがない場合はエラー
        if (empty($data)) {
            \Log::error('No data available for Excel export');
            throw new \Exception('精算データが見つかりません。データの生成に失敗した可能性があります。');
        }

        // 委託先ごとのシート（テンプレート方式）
        foreach ($data as $clientCode => $clientData) {
            $sheets[] = new SettlementClientSheet(
                $this->settlement,
                $clientData,
                $this->templateService
            );
        }

        return $sheets;
    }

    /**
     * SettlementDetail から配列形式に変換
     */
    private function convertDetailsToArray(): array
    {
        $data = [];

        foreach ($this->settlement->details as $detail) {
            $data[$detail->client_code] = [
                'client_code' => $detail->client_code,
                'client_name' => $detail->client_name,
                'postal_code' => $detail->postal_code ?? '',
                'address' => $detail->address ?? '',
                'bank_name' => $detail->bank_name ?? '',
                'branch_name' => $detail->branch_name ?? '',
                'account_type' => $detail->account_type ?? '',
                'account_number' => $detail->account_number ?? '',
                'account_name' => $detail->account_name ?? '',
                'sales_amount' => $detail->sales_amount,
                'commission_amount' => $detail->commission_amount,
                'payment_amount' => $detail->payment_amount,
                'sales_count' => $detail->sales_count,
                'sales_details' => $detail->sales_details ?? [],
            ];
        }

        return $data;
    }
}

/**
 * 委託先別シート（テンプレートベース）
 *
 * テンプレートファイルを読み込み、データを埋め込む
 */
class SettlementClientSheet implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithEvents, \Maatwebsite\Excel\Concerns\WithTitle
{
    /**
     * コンストラクタ
     */
    public function __construct(
        private readonly Settlement $settlement,
        private readonly array $clientData,
        private readonly SettlementTemplateService $templateService
    ) {}

    /**
     * コレクションを返す（空配列でOK、実際のデータはafterSheetで書き込む）
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // FromCollection を実装する必要があるが、
        // 実際のデータ書き込みはafterSheetで行うため、ここでは空のコレクションを返す
        return collect([]);
    }

    /**
     * シート作成後の処理
     */
    public function registerEvents(): array
    {
        return [
            \Maatwebsite\Excel\Events\AfterSheet::class => function (\Maatwebsite\Excel\Events\AfterSheet $event) {
                // テンプレートを読み込んでデータを書き込む
                $this->fillTemplateToSheet($event->sheet);
            },
        ];
    }

    /**
     * テンプレートをシートに適用
     *
     * @param  \Maatwebsite\Excel\Sheet  $sheet
     */
    private function fillTemplateToSheet($sheet): void
    {
        try {
            // テンプレートを読み込む
            $spreadsheet = $this->templateService->loadTemplate();

            // テンプレートにデータを書き込む
            $this->templateService->fillTemplate(
                $spreadsheet,
                $this->settlement,
                $this->clientData
            );

            // テンプレートのワークシートを現在のシートにコピー
            $templateSheet = $spreadsheet->getActiveSheet();
            $this->copySheetContent($templateSheet, $sheet->getDelegate());

            \Log::info('Template applied to sheet', [
                'client_code' => $this->clientData['client_code'] ?? 'unknown',
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to apply template to sheet', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * シートの内容をコピー
     *
     * @param  \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet  $source
     * @param  \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet  $target
     */
    private function copySheetContent($source, $target): void
    {
        // すべてのセルをコピー
        foreach ($source->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $coordinate = $cell->getCoordinate();

                // セルの値をコピー
                $target->setCellValue($coordinate, $cell->getValue());

                // スタイルをコピー
                $target->duplicateStyle(
                    $source->getStyle($coordinate),
                    $coordinate
                );
            }
        }

        // 列幅をコピー
        foreach ($source->getColumnIterator() as $column) {
            $columnIndex = $column->getColumnIndex();
            $target->getColumnDimension($columnIndex)->setWidth(
                $source->getColumnDimension($columnIndex)->getWidth()
            );
        }

        // 行の高さをコピー
        foreach ($source->getRowIterator() as $row) {
            $rowIndex = $row->getRowIndex();
            $target->getRowDimension($rowIndex)->setRowHeight(
                $source->getRowDimension($rowIndex)->getRowHeight()
            );
        }

        // セルの結合をコピー
        foreach ($source->getMergeCells() as $mergeCell) {
            $target->mergeCells($mergeCell);
        }
    }

    /**
     * シート名を返す
     */
    public function title(): string
    {
        return mb_substr($this->clientData['client_name'], 0, 31);
    }
}
