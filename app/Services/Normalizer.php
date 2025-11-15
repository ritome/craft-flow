<?php

declare(strict_types=1);

namespace App\Services;

/**
 * パース結果を正規化するサービス
 *
 * 各レジパーサーの出力形式を統一し、
 * 不足しているキーの補完や型変換を行う
 */
class Normalizer
{
    /**
     * パース結果を正規化
     *
     * @param  array  $parsedData  パース済みデータ
     * @return array 正規化されたデータ
     */
    public function normalize(array $parsedData): array
    {
        // PosRegisterParser形式（新仕様）
        if (isset($parsedData['register_id'])) {
            return $this->normalizePosRegister($parsedData);
        }

        // 旧形式（互換性のため保持）
        return [
            'date' => $this->normalizeDate($parsedData),
            'items' => $this->normalizeItems($parsedData),
            'total' => $this->normalizeTotal($parsedData),
            'metadata' => $this->normalizeMetadata($parsedData),
        ];
    }

    /**
     * POSレジ形式のデータを正規化
     */
    private function normalizePosRegister(array $data): array
    {
        return [
            'register_id' => $this->normalizeString($data['register_id'] ?? 'UNKNOWN'),
            'business_date' => $this->normalizeDate($data),
            'output_datetime' => $data['output_datetime'] ?? date('Y-m-d H:i:s'),
            'items' => $this->normalizePosRegisterItems($data['items'] ?? []),
            'total' => $this->normalizeInteger($data['total'] ?? 0),
            'product_count' => count(array_filter(
                $data['items'] ?? [],
                fn ($item) => ($item['quantity'] ?? 0) > 0
            )),
            'quantity_total' => array_sum(array_column($data['items'] ?? [], 'quantity')),
        ];
    }

    /**
     * POSレジ商品データを正規化
     */
    private function normalizePosRegisterItems(array $items): array
    {
        return array_map(function ($item) {
            return [
                'product_code' => $this->normalizeString($item['product_code'] ?? ''),
                'product_name' => $this->normalizeString($item['product_name'] ?? '不明'),
                'unit_price' => $this->normalizeInteger($item['unit_price'] ?? 0),
                'quantity' => $this->normalizeInteger($item['quantity'] ?? 0),
                'subtotal' => $this->normalizeInteger($item['subtotal'] ?? 0),
            ];
        }, $items);
    }

    /**
     * 日付を正規化（YYYY-MM-DD形式）
     */
    private function normalizeDate(array $data): string
    {
        // POSレジ形式の場合
        if (isset($data['business_date'])) {
            $date = $data['business_date'];
        } else {
            $date = $data['date'] ?? date('Y-m-d');
        }

        // 既にYYYY-MM-DD形式ならそのまま返す
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        // その他の形式の場合は変換を試みる
        try {
            return date('Y-m-d', strtotime($date));
        } catch (\Exception $e) {
            return date('Y-m-d');
        }
    }

    /**
     * 商品アイテムを正規化
     */
    private function normalizeItems(array $data): array
    {
        $items = $data['items'] ?? [];

        if (! is_array($items)) {
            return [];
        }

        return array_map(function ($item) {
            return [
                'name' => $this->normalizeString($item['name'] ?? '不明'),
                'qty' => $this->normalizeInteger($item['qty'] ?? $item['quantity'] ?? 0),
                'price' => $this->normalizeInteger($item['price'] ?? $item['amount'] ?? 0),
                'unit_price' => $this->normalizeInteger($item['unit_price'] ?? 0),
            ];
        }, $items);
    }

    /**
     * 合計金額を正規化
     */
    private function normalizeTotal(array $data): int
    {
        // total が設定されている場合はそれを使用
        if (isset($data['total'])) {
            return $this->normalizeInteger($data['total']);
        }

        // total がない場合はitemsから計算
        $items = $data['items'] ?? [];
        if (! is_array($items)) {
            return 0;
        }

        return array_sum(array_map(
            fn ($item) => $this->normalizeInteger($item['price'] ?? $item['amount'] ?? 0),
            $items
        ));
    }

    /**
     * メタデータを正規化
     */
    private function normalizeMetadata(array $data): array
    {
        return [
            'source' => $this->normalizeString($data['source'] ?? 'unknown'),
            'parser' => $this->normalizeString($data['parser'] ?? 'unknown'),
            'imported_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * 文字列を正規化
     */
    private function normalizeString(mixed $value): string
    {
        if (is_string($value)) {
            return trim($value);
        }

        return (string) $value;
    }

    /**
     * 整数を正規化
     */
    private function normalizeInteger(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        // カンマ区切りの数値文字列に対応
        if (is_string($value)) {
            $cleaned = str_replace([',', '円', '￥'], '', $value);
            if (is_numeric($cleaned)) {
                return (int) $cleaned;
            }
        }

        return 0;
    }
}
