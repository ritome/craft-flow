<?php

declare(strict_types=1);

namespace App\Services\Parsers;

use InvalidArgumentException;

/**
 * POSレジAフォーマット用パーサー
 *
 * 想定フォーマット:
 * ========================================
 * レジA 売上レポート
 * 日付: 2024/01/15
 * ========================================
 * 商品名             数量  単価   金額
 * ----------------------------------------
 * コーヒー            2    300    600
 * サンドイッチ        1    500    500
 * ----------------------------------------
 * 合計                            1100円
 * ========================================
 */
class PosAParser implements ParserInterface
{
    private const DATE_PATTERN = '/日付[:\s]*(\d{4})[\/\-年](\d{1,2})[\/\-月](\d{1,2})/u';

    private const ITEM_PATTERN = '/^(.+?)\s+(\d+)\s+(\d+)\s+(\d+)/mu';

    private const TOTAL_PATTERN = '/合計[:\s]*(\d+)/u';

    /**
     * このパーサーが対応可能なフォーマットかチェック
     */
    public function canParse(string $text): bool
    {
        return str_contains($text, 'レジA') &&
            str_contains($text, '売上レポート');
    }

    /**
     * PDFテキストをパースして配列に変換
     *
     * @throws InvalidArgumentException パース失敗時
     */
    public function parse(string $text): array
    {
        $date = $this->extractDate($text);
        $items = $this->extractItems($text);
        $total = $this->extractTotal($text);

        // 検証: itemsの合計とtotalが一致するか
        $calculatedTotal = array_sum(array_column($items, 'price'));
        if ($calculatedTotal !== $total) {
            throw new InvalidArgumentException(
                "合計金額が一致しません。計算値: {$calculatedTotal}, 記載値: {$total}"
            );
        }

        return [
            'date' => $date,
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * 日付を抽出
     */
    private function extractDate(string $text): string
    {
        if (preg_match(self::DATE_PATTERN, $text, $matches)) {
            $year = $matches[1];
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $day = str_pad($matches[3], 2, '0', STR_PAD_LEFT);

            return "{$year}-{$month}-{$day}";
        }

        throw new InvalidArgumentException('日付が見つかりませんでした');
    }

    /**
     * 商品アイテムを抽出
     */
    private function extractItems(string $text): array
    {
        $items = [];
        $lines = explode("\n", $text);

        foreach ($lines as $line) {
            if (preg_match(self::ITEM_PATTERN, trim($line), $matches)) {
                $items[] = [
                    'name' => trim($matches[1]),
                    'qty' => (int) $matches[2],
                    'price' => (int) $matches[4], // 金額列
                ];
            }
        }

        if (empty($items)) {
            throw new InvalidArgumentException('商品情報が見つかりませんでした');
        }

        return $items;
    }

    /**
     * 合計金額を抽出
     */
    private function extractTotal(string $text): int
    {
        if (preg_match(self::TOTAL_PATTERN, $text, $matches)) {
            return (int) $matches[1];
        }

        throw new InvalidArgumentException('合計金額が見つかりませんでした');
    }
}
