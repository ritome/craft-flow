<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * 売上データをExcel形式でエクスポート
 */
class SalesDataExport implements WithMultipleSheets
{
    public function __construct(
        private readonly array $aggregatedData
    ) {}

    /**
     * 複数シートを返す
     */
    public function sheets(): array
    {
        return [
            new SummarySheet($this->aggregatedData),
            new ItemsSheet($this->aggregatedData),
            new DailySalesSheet($this->aggregatedData),
        ];
    }
}

/**
 * サマリーシート
 */
class SummarySheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private readonly array $aggregatedData
    ) {}

    public function title(): string
    {
        return 'サマリー';
    }

    public function headings(): array
    {
        return [
            '項目',
            '値',
        ];
    }

    public function array(): array
    {
        $summary = $this->aggregatedData['summary'] ?? [];
        $dateRange = $summary['date_range'] ?? [];

        return [
            ['総売上', number_format($this->aggregatedData['total_sales'] ?? 0).'円'],
            ['ファイル数', $summary['total_files'] ?? 0],
            ['商品種類数', $summary['unique_items_count'] ?? 0],
            ['総商品数', $summary['total_items_count'] ?? 0],
            ['集計期間（開始）', $dateRange['start'] ?? ''],
            ['集計期間（終了）', $dateRange['end'] ?? ''],
            ['出力日時', date('Y-m-d H:i:s')],
        ];
    }
}

/**
 * 商品別売上シート
 */
class ItemsSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private readonly array $aggregatedData
    ) {}

    public function title(): string
    {
        return '商品別売上';
    }

    public function headings(): array
    {
        return [
            '商品名',
            '販売数量',
            '売上金額',
            '平均単価',
            '取引回数',
        ];
    }

    public function array(): array
    {
        $items = $this->aggregatedData['items'] ?? [];

        return array_map(function ($item) {
            $avgPrice = $item['total_qty'] > 0
                ? round($item['total_price'] / $item['total_qty'])
                : 0;

            return [
                $item['name'],
                $item['total_qty'],
                number_format($item['total_price']).'円',
                number_format($avgPrice).'円',
                $item['count'],
            ];
        }, $items);
    }
}

/**
 * 日別売上シート
 */
class DailySalesSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private readonly array $aggregatedData
    ) {}

    public function title(): string
    {
        return '日別売上';
    }

    public function headings(): array
    {
        return [
            '日付',
            '売上金額',
            '商品数',
            'ファイル数',
        ];
    }

    public function array(): array
    {
        $dailySales = $this->aggregatedData['daily_sales'] ?? [];

        return array_map(function ($sale) {
            return [
                $sale['date'],
                number_format($sale['sales']).'円',
                $sale['items_count'],
                $sale['files_count'],
            ];
        }, $dailySales);
    }
}
