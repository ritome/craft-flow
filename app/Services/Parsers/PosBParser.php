<?php

declare(strict_types=1);

namespace App\Services\Parsers;

use InvalidArgumentException;

/**
 * POSレジBフォーマット用パーサー
 *
 * 想定フォーマット:
 * ========================================
 * *** POS-B システム ***
 * 営業日: 2024-01-15
 * ========================================
 * [1] カフェラテ x 3 = ¥450
 * [2] クロワッサン x 1 = ¥280
 * [3] オレンジジュース x 2 = ¥400
 * ========================================
 * 総計: ¥1,130
 * ========================================
 */
class PosBParser implements ParserInterface
{
    private const DATE_PATTERN = '/営業日[:\s]*(\d{4})[\/\-年](\d{1,2})[\/\-月](\d{1,2})/u';

    private const ITEM_PATTERN = '/\[\d+\]\s*(.+?)\s*x\s*(\d+)\s*=\s*[¥￥]?([\d,]+)/u';

    private const TOTAL_PATTERN = '/総計[:\s]*[¥￥]?([\d,]+)/u';

    /**
     * このパーサーが対応可能なフォーマットかチェック
     */
    public function canParse(string $text): bool
    {
        return str_contains($text, 'POS-B') &&
               str_contains($text, '営業日');
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

        if (preg_match_all(self::ITEM_PATTERN, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $items[] = [
                    'name' => trim($match[1]),
                    'qty' => (int) $match[2],
                    'price' => (int) str_replace(',', '', $match[3]),
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
            return (int) str_replace(',', '', $matches[1]);
        }

        throw new InvalidArgumentException('合計金額が見つかりませんでした');
    }
}
