<?php

declare(strict_types=1);

namespace App\Services\Parsers;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * POSレジ用パーサー（岩手県センター仕様）
 *
 * 対応フォーマット:
 * - レジ番号: POS1〜POS4
 * - 営業日: 令和年表記
 * - 商品データ: 2カラム形式
 * - 商品数: 常に30品目
 */
class PosRegisterParser implements ParserInterface
{
    /**
     * レジ番号パターン
     */
    private const REGISTER_PATTERN = '/レジ番号:POS(\d+)/u';

    /**
     * 営業日パターン
     */
    private const BUSINESS_DATE_PATTERN = '/営業日:令和(\d+)年(\d+)月(\d+)日/u';

    /**
     * 出力日時パターン
     */
    private const OUTPUT_DATETIME_PATTERN = '/出力日時:令和(\d+)年(\d+)月(\d+)日\s+(\d+)時(\d+)分/u';

    /**
     * 商品データ行パターン（2カラム）
     */
    private const ITEM_LINE_PATTERN = '/(P\d{3})\s+(.+?)\s+¥([\d,]+)\s+(\d+)\s+¥([\d,]+)\s+(P\d{3})\s+(.+?)\s+¥([\d,]+)\s+(\d+)\s+¥([\d,]+)/u';

    /**
     * 合計金額パターン
     */
    private const TOTAL_PATTERN = '/合計\s+¥([\d,]+)/u';

    /**
     * 期待される商品数（固定）
     */
    private const EXPECTED_ITEM_COUNT = 30;

    /**
     * パース中にカウントした全商品数（数量0を含む）
     */
    private int $allItemsCount = 0;

    /**
     * このパーサーが対応可能なフォーマットかチェック
     */
    public function canParse(string $text): bool
    {
        // レジ番号と営業日が含まれていればPOSレジフォーマットと判定
        return preg_match(self::REGISTER_PATTERN, $text) === 1
            && preg_match(self::BUSINESS_DATE_PATTERN, $text) === 1;
    }

    /**
     * PDFテキストをパースして配列に変換
     *
     * @throws InvalidArgumentException パース失敗時
     */
    public function parse(string $text): array
    {
        Log::info('PosRegisterParser: パース開始');

        // 1. ヘッダー情報を抽出
        $registerId = $this->parseRegisterId($text);
        $businessDate = $this->parseBusinessDate($text);
        $outputDatetime = $this->parseOutputDatetime($text);

        // 2. 商品データを抽出
        $items = $this->parseItems($text);

        // 3. 合計金額を抽出
        $total = $this->parseTotal($text);

        // 4. データ検証
        $this->validate($items, $total);

        Log::info('PosRegisterParser: パース完了', [
            'register_id' => $registerId,
            'business_date' => $businessDate,
            'items_count' => count($items),
            'all_items_count' => $this->allItemsCount,
            'total' => $total,
        ]);

        return [
            'register_id' => $registerId,
            'business_date' => $businessDate,
            'output_datetime' => $outputDatetime,
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * レジ番号を抽出
     */
    private function parseRegisterId(string $text): string
    {
        if (preg_match(self::REGISTER_PATTERN, $text, $matches)) {
            return 'POS'.$matches[1];
        }

        throw new InvalidArgumentException('レジ番号が見つかりませんでした');
    }

    /**
     * 営業日を抽出
     */
    private function parseBusinessDate(string $text): string
    {
        if (preg_match(self::BUSINESS_DATE_PATTERN, $text, $matches)) {
            $reiwaYear = (int) $matches[1];
            $month = (int) $matches[2];
            $day = (int) $matches[3];

            return $this->convertReiwaToDate($reiwaYear, $month, $day);
        }

        throw new InvalidArgumentException('営業日が見つかりませんでした');
    }

    /**
     * 出力日時を抽出
     */
    private function parseOutputDatetime(string $text): string
    {
        if (preg_match(self::OUTPUT_DATETIME_PATTERN, $text, $matches)) {
            $reiwaYear = (int) $matches[1];
            $month = (int) $matches[2];
            $day = (int) $matches[3];
            $hour = (int) $matches[4];
            $minute = (int) $matches[5];

            return $this->convertReiwaToDatetime($reiwaYear, $month, $day, $hour, $minute);
        }

        throw new InvalidArgumentException('出力日時が見つかりませんでした');
    }

    /**
     * 商品データを抽出
     */
    private function parseItems(string $text): array
    {
        $items = [];
        $this->allItemsCount = 0;
        $lines = explode("\n", $text);

        foreach ($lines as $line) {
            // 2カラム形式の行をマッチ
            if (preg_match(self::ITEM_LINE_PATTERN, $line, $matches)) {
                // 左カラム
                $leftItem = $this->buildItem(
                    $matches[1],  // 商品コード
                    $matches[2],  // 商品名
                    $matches[3],  // 単価
                    $matches[4],  // 数量
                    $matches[5]   // 小計
                );

                $this->allItemsCount++;

                // 数量が0より大きい場合のみ追加
                if ($leftItem['quantity'] > 0) {
                    $items[] = $leftItem;
                }

                // 右カラム
                $rightItem = $this->buildItem(
                    $matches[6],  // 商品コード
                    $matches[7],  // 商品名
                    $matches[8],  // 単価
                    $matches[9],  // 数量
                    $matches[10]  // 小計
                );

                $this->allItemsCount++;

                // 数量が0より大きい場合のみ追加
                if ($rightItem['quantity'] > 0) {
                    $items[] = $rightItem;
                }
            }
        }

        if (empty($items)) {
            throw new InvalidArgumentException('商品データが見つかりませんでした');
        }

        return $items;
    }

    /**
     * 商品アイテムデータを構築
     */
    private function buildItem(
        string $productCode,
        string $productName,
        string $unitPrice,
        string $quantity,
        string $subtotal
    ): array {
        return [
            'product_code' => trim($productCode),
            'product_name' => trim($productName),
            'unit_price' => $this->parsePrice($unitPrice),
            'quantity' => (int) $quantity,
            'subtotal' => $this->parsePrice($subtotal),
        ];
    }

    /**
     * 金額文字列をintに変換
     */
    private function parsePrice(string $priceString): int
    {
        // "8,500" → 8500
        $cleaned = str_replace(',', '', $priceString);

        return (int) $cleaned;
    }

    /**
     * 合計金額を抽出
     */
    private function parseTotal(string $text): int
    {
        if (preg_match(self::TOTAL_PATTERN, $text, $matches)) {
            return $this->parsePrice($matches[1]);
        }

        throw new InvalidArgumentException('合計金額が見つかりませんでした');
    }

    /**
     * 令和年を西暦に変換してDate形式にする
     */
    private function convertReiwaToDate(int $reiwaYear, int $month, int $day): string
    {
        // 令和元年 = 2019年
        $year = 2018 + $reiwaYear;

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * 令和年を西暦に変換してDatetime形式にする
     */
    private function convertReiwaToDatetime(
        int $reiwaYear,
        int $month,
        int $day,
        int $hour,
        int $minute
    ): string {
        $year = 2018 + $reiwaYear;

        return sprintf('%04d-%02d-%02d %02d:%02d:00', $year, $month, $day, $hour, $minute);
    }

    /**
     * パースしたデータを検証
     */
    private function validate(array $items, int $total): void
    {
        // 各商品の小計を検証
        foreach ($items as $item) {
            $expectedSubtotal = $item['unit_price'] * $item['quantity'];

            if ($item['subtotal'] !== $expectedSubtotal) {
                Log::warning('PosRegisterParser: 小計が一致しません', [
                    'product_code' => $item['product_code'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'expected' => $expectedSubtotal,
                    'actual' => $item['subtotal'],
                ]);
            }
        }

        // 合計金額を検証
        $calculatedTotal = array_sum(array_column($items, 'subtotal'));

        if ($calculatedTotal !== $total) {
            Log::warning('PosRegisterParser: 合計金額が一致しません', [
                'calculated' => $calculatedTotal,
                'pdf_total' => $total,
                'difference' => abs($calculatedTotal - $total),
            ]);
        }

        // 商品数を検証（常に30品目であることを確認）
        if ($this->allItemsCount !== self::EXPECTED_ITEM_COUNT) {
            Log::warning('PosRegisterParser: 商品数が30品目ではありません', [
                'expected' => self::EXPECTED_ITEM_COUNT,
                'actual' => $this->allItemsCount,
            ]);
        }
    }
}


