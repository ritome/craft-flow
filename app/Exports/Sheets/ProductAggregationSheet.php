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
 * シート2: 商品別集計
 *
 * 商品ごとの販売数量・金額を集計
 */
class ProductAggregationSheet implements FromArray, WithHeadings, WithTitle, WithStyles
{
    public function __construct(
        private readonly array $aggregatedData
    ) {}

    public function title(): string
    {
        return '商品別集計';
    }

    public function headings(): array
    {
        return [
            '商品コード',
            '商品名',
            '単価',
            '販売数量',
            '売上金額',
        ];
    }

    public function array(): array
    {
        $data = [];

        // 商品別データ行
        foreach ($this->aggregatedData['products'] ?? [] as $product) {
            $data[] = [
                $product['product_code'],
                $product['product_name'],
                '¥'.number_format($product['unit_price']),
                $product['total_quantity'],
                '¥'.number_format($product['total_sales']),
            ];
        }

        // 合計行
        $summary = $this->aggregatedData['summary'] ?? [];
        $data[] = [
            '合計',
            '',
            '',
            $summary['total_quantity'] ?? 0,
            '¥'.number_format($summary['total_sales'] ?? 0),
        ];

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        $rowCount = count($this->aggregatedData['products'] ?? []) + 2; // ヘッダー + 合計行

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

