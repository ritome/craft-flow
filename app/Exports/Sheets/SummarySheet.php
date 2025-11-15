<?php

declare(strict_types=1);

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * シート1: 集計サマリー
 *
 * 全体の売上概要とレジ別の小計を表示
 */
class SummarySheet implements FromArray, WithHeadings, WithTitle, WithStyles
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

        // レジ別のデータ行
        foreach ($this->aggregatedData['registers'] ?? [] as $register) {
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
}

