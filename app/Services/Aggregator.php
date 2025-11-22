<?php

declare(strict_types=1);

namespace App\Services;

/**
 * 正規化されたデータを集計するサービス
 *
 * 複数のPDFパース結果を集計し、
 * Excel出力用のデータ構造を生成する
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

        // POSレジ形式かどうかを判定
        $firstData = $normalizedDataList[0];
        if (isset($firstData['register_id'])) {
            return $this->aggregatePosRegisters($normalizedDataList);
        }

        // 旧形式（互換性のため保持）
        return $this->aggregateLegacy($normalizedDataList);
    }

    /**
     * POSレジデータを集計
     */
    private function aggregatePosRegisters(array $normalizedDataList): array
    {
        $businessDate = $normalizedDataList[0]['business_date'] ?? date('Y-m-d');
        $registers = [];
        $productMap = [];
        $totalQuantity = 0;
        $totalSales = 0;

        // レジ別に集計
        foreach ($normalizedDataList as $data) {
            $registers[] = [
                'register_id' => $data['register_id'],
                'output_datetime' => $data['output_datetime'],
                'items' => $data['items'],
                'product_count' => $data['product_count'] ?? 0,
                'quantity_total' => $data['quantity_total'] ?? 0,
                'sales_total' => $data['total'] ?? 0,
            ];

            // 商品別に集計
            foreach ($data['items'] as $item) {
                $code = $item['product_code'];

                if (!isset($productMap[$code])) {
                    $productMap[$code] = [
                        'product_code' => $code,
                        'product_name' => $item['product_name'],
                        'unit_price' => $item['unit_price'],
                        'total_quantity' => 0,
                        'total_sales' => 0,
                    ];
                }

                $productMap[$code]['total_quantity'] += $item['quantity'];
                $productMap[$code]['total_sales'] += $item['subtotal'];
            }

            $totalQuantity += $data['quantity_total'] ?? 0;
            $totalSales += $data['total'] ?? 0;
        }

        // 商品を売上順にソート
        $products = array_values($productMap);
        usort($products, fn ($a, $b) => $b['total_sales'] <=> $a['total_sales']);

        return [
            'business_date' => $businessDate,
            'generated_at' => date('Y-m-d H:i:s'),
            'file_count' => count($normalizedDataList),
            'success_count' => count($normalizedDataList),
            'failed_count' => 0,
            'registers' => $registers,
            'products' => $products,
            'summary' => [
                'total_product_types' => count($productMap),
                'total_quantity' => $totalQuantity,
                'total_sales' => $totalSales,
            ],
        ];
    }

    /**
     * 旧形式のデータを集計（互換性のため）
     */
    private function aggregateLegacy(array $normalizedDataList): array
    {
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
