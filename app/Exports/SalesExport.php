<?php

declare(strict_types=1);

namespace App\Exports;

use App\Exports\Sheets\ProductAggregationSheet;
use App\Exports\Sheets\RegisterDetailSheet;
use App\Exports\Sheets\SummarySheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * 売上データをExcel形式でエクスポート（3シート構成）
 *
 * シート構成:
 * 1. 集計サマリー: レジ別の集計概要
 * 2. 商品別集計: 商品ごとの販売数量・金額
 * 3. レジ別詳細: 各レジの詳細データ
 */
class SalesExport implements WithMultipleSheets
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
            new ProductAggregationSheet($this->aggregatedData),
            new RegisterDetailSheet($this->aggregatedData),
        ];
    }
}
