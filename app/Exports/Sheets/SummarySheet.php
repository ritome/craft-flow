<?php

declare(strict_types=1);

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * シート1: 集計サマリー
 *
 * 全体の売上概要とレジ別の小計を表示
 */
class SummarySheet implements FromArray, WithColumnWidths, WithEvents, WithHeadings, WithStyles, WithTitle
{
    public function __construct(
        private readonly array $aggregatedData
    ) {}

    public function title(): string
    {
        return '集計サマリー';
    }

    public function headings(): array
    {
        return [
            'レジ番号',
            '処理日時',
            '販売商品数',
            '販売数量合計',
            '売上金額',
        ];
    }

    public function array(): array
    {
        $data = [];

        // レジ別のデータ行(レジ番号の昇順にソート)
        $registers = $this->aggregatedData['registers'] ?? [];
        usort($registers, function ($a, $b) {
            return $a['register_id'] <=> $b['register_id'];
        });

        foreach ($registers as $register) { // ソート後のデータでループ
            $data[] = [
                $register['register_id'],
                $register['output_datetime'],
                $register['product_count'],
                $register['quantity_total'],
                '¥'.number_format($register['sales_total']),
            ];
        }

        // 合計行
        $summary = $this->aggregatedData['summary'] ?? [];
        $data[] = [
            '合計',
            '',
            '', // 販売商品数の合計は意味がないため空欄
            $summary['total_quantity'] ?? 0,
            '¥'.number_format($summary['total_sales'] ?? 0),
        ];

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        $rowCount = count($this->aggregatedData['registers'] ?? []) + 2; // ヘッダー + 合計行

        return [
            // ヘッダー行のスタイル
            1 => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'D9E2F3'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],

            // 合計行のスタイル
            $rowCount => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'FFF2CC'],
                ],
            ],
        ];
    }

    // 出力シートのカラム幅定義
    public function columnWidths(): array
    {
        return [
            'A' => 15,  // レジ番号
            'B' => 45,  // 処理日時
            'C' => 20,  // 販売商品数
            'D' => 25,  // 販売数量合計
            'E' => 20,  // 売上金額
        ];
    }

    // ヘッダー行にオートフィルタを設定
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // ヘッダー行にオートフィルタを設定
                $lastColumn = 'E'; // 最後の列（売上金額）
                $lastRow = count($this->aggregatedData['registers'] ?? []) + 1; // レジ数 + ヘッダー
                $event->sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");
            },
        ];
    }
}
