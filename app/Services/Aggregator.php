<?php

declare(strict_types=1);

namespace App\Services;

/**
 * 正規化されたデータを集計するサービス
 *
 * 複数のPDFパース結果を集計し、
 * 全体の売上や商品別の集計を行う
 */
class Aggregator
{
    /**
     * 複数のデータを集計
     *
     * @param  array  $normalizedDataList  正規化されたデータの配列
     * @return array 集計結果
     */
    public function aggregate(array $normalizedDataList): array
    {
        if (empty($normalizedDataList)) {
            return $this->emptyResult();
        }

        $totalSales = 0;
        $itemsMap = [];
        $dailySales = [];
        $sourceCount = [];

        foreach ($normalizedDataList as $data) {
            // 全体の売上合計
            $totalSales += $data['total'] ?? 0;

            // 商品別の集計
            $this->aggregateItems($itemsMap, $data['items'] ?? []);

            // 日別の集計
            $this->aggregateDailySales($dailySales, $data);

            // ソース別のカウント
            $source = $data['metadata']['source'] ?? 'unknown';
            $sourceCount[$source] = ($sourceCount[$source] ?? 0) + 1;
        }

        return [
            'total_sales' => $totalSales,
            'items' => $this->formatItems($itemsMap),
            'daily_sales' => $this->formatDailySales($dailySales),
            'summary' => [
                'total_files' => count($normalizedDataList),
                'total_items_count' => array_sum(array_column($itemsMap, 'total_qty')),
                'unique_items_count' => count($itemsMap),
                'date_range' => $this->getDateRange($normalizedDataList),
                'sources' => $sourceCount,
            ],
        ];
    }

    /**
     * 商品別に集計
     */
    private function aggregateItems(array &$itemsMap, array $items): void
    {
        foreach ($items as $item) {
            $name = $item['name'];

            if (! isset($itemsMap[$name])) {
                $itemsMap[$name] = [
                    'name' => $name,
                    'total_qty' => 0,
                    'total_price' => 0,
                    'count' => 0,
                ];
            }

            $itemsMap[$name]['total_qty'] += $item['qty'];
            $itemsMap[$name]['total_price'] += $item['price'];
            $itemsMap[$name]['count']++;
        }
    }

    /**
     * 日別売上を集計
     */
    private function aggregateDailySales(array &$dailySales, array $data): void
    {
        $date = $data['date'] ?? date('Y-m-d');

        if (! isset($dailySales[$date])) {
            $dailySales[$date] = [
                'date' => $date,
                'sales' => 0,
                'items_count' => 0,
                'files_count' => 0,
            ];
        }

        $dailySales[$date]['sales'] += $data['total'] ?? 0;
        $dailySales[$date]['items_count'] += count($data['items'] ?? []);
        $dailySales[$date]['files_count']++;
    }

    /**
     * 商品データをフォーマット
     */
    private function formatItems(array $itemsMap): array
    {
        $items = array_values($itemsMap);

        // 売上金額の降順でソート
        usort($items, fn ($a, $b) => $b['total_price'] <=> $a['total_price']);

        return $items;
    }

    /**
     * 日別売上をフォーマット
     */
    private function formatDailySales(array $dailySales): array
    {
        $sales = array_values($dailySales);

        // 日付の昇順でソート
        usort($sales, fn ($a, $b) => $a['date'] <=> $b['date']);

        return $sales;
    }

    /**
     * 日付範囲を取得
     */
    private function getDateRange(array $normalizedDataList): array
    {
        $dates = array_map(
            fn ($data) => $data['date'] ?? '',
            $normalizedDataList
        );

        $dates = array_filter($dates);

        if (empty($dates)) {
            return [
                'start' => null,
                'end' => null,
            ];
        }

        sort($dates);

        return [
            'start' => reset($dates),
            'end' => end($dates),
        ];
    }

    /**
     * 空の結果を返す
     */
    private function emptyResult(): array
    {
        return [
            'total_sales' => 0,
            'items' => [],
            'daily_sales' => [],
            'summary' => [
                'total_files' => 0,
                'total_items_count' => 0,
                'unique_items_count' => 0,
                'date_range' => [
                    'start' => null,
                    'end' => null,
                ],
                'sources' => [],
            ],
        ];
    }
}
