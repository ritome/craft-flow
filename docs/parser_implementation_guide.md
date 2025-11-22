# パーサー実装ガイド

**ドキュメント名**: POSレジPDFパーサー実装ガイド  
**バージョン**: 1.0.0  
**作成日**: 2025-11-15  
**対象開発者**: バックエンドエンジニア

---

## 目次

1. [概要](#1-概要)
2. [パーサーアーキテクチャ](#2-パーサーアーキテクチャ)
3. [PosRegisterParser実装](#3-posregisterparser実装)
4. [正規表現パターン](#4-正規表現パターン)
5. [実装コード例](#5-実装コード例)
6. [テスト実装](#6-テスト実装)
7. [デバッグ方法](#7-デバッグ方法)
8. [トラブルシューティング](#8-トラブルシューティング)

---

## 1. 概要

### 1.1 目的

POSレジから出力されたPDFのテキストデータを解析し、構造化されたデータに変換するパーサーを実装する。

### 1.2 入力・出力

**入力**: PDFから抽出されたテキスト（UTF-8文字列）

```
レジ番号:POS1
営業日:令和7年11月5日
出力日時:令和7年11月6日 17時30分

商品コード 商品名 単価 数量 小計 商品コード 商品名 単価 数量 小計
P001 南部鉄器 急須(小) ¥8,500 1 ¥8,500 P016 わら細工 鍋敷き ¥800 0 ¥0
...
合計 ¥89,910
```

**出力**: PHP配列（構造化データ）

```php
[
    'register_id' => 'POS1',
    'business_date' => '2025-11-05',
    'output_datetime' => '2025-11-06 17:30:00',
    'items' => [
        [
            'product_code' => 'P001',
            'product_name' => '南部鉄器 急須(小)',
            'unit_price' => 8500,
            'quantity' => 1,
            'subtotal' => 8500,
        ],
        // ...
    ],
    'total' => 89910,
]
```

---

## 2. パーサーアーキテクチャ

### 2.1 クラス設計

```
ParserInterface (インターフェース)
    │
    ├── PosRegisterParser (POSレジ用パーサー)
    ├── [Future] PosRegisterParserV2 (将来の別フォーマット用)
    └── [Future] GenericParser (汎用パーサー)
```

### 2.2 ファイル構成

```
app/Services/Parsers/
├── ParserInterface.php         # パーサーインターフェース
├── PosRegisterParser.php       # POSレジパーサー実装
└── Concerns/
    ├── ParsesHeader.php        # ヘッダー解析トレイト
    ├── ParsesItems.php         # 商品データ解析トレイト
    └── ParsesFooter.php        # フッター解析トレイト
```

---

## 3. PosRegisterParser実装

### 3.1 ParserInterfaceの定義

```php
<?php

declare(strict_types=1);

namespace App\Services\Parsers;

interface ParserInterface
{
    /**
     * PDFから抽出したテキストをパースする
     *
     * @param string $text PDFテキスト
     * @return array 構造化データ
     * @throws \InvalidArgumentException パースできない場合
     */
    public function parse(string $text): array;

    /**
     * このパーサーが対応するフォーマットかどうかを判定
     *
     * @param string $text PDFテキスト
     * @return bool 対応している場合はtrue
     */
    public function canParse(string $text): bool;
}
```

### 3.2 PosRegisterParserの基本構造

```php
<?php

declare(strict_types=1);

namespace App\Services\Parsers;

use InvalidArgumentException;
use Illuminate\Support\Facades\Log;

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

    public function canParse(string $text): bool
    {
        // レジ番号と営業日が含まれていればPOSレジフォーマットと判定
        return preg_match(self::REGISTER_PATTERN, $text) === 1
            && preg_match(self::BUSINESS_DATE_PATTERN, $text) === 1;
    }

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

    // 以下、各メソッドの実装...
}
```

---

## 4. 正規表現パターン

### 4.1 ヘッダー部分

#### レジ番号

```php
private const REGISTER_PATTERN = '/レジ番号:POS(\d+)/u';

// マッチ例: "レジ番号:POS1"
// キャプチャ: グループ1 = "1"
```

**テスト**:
```php
$text = "レジ番号:POS1";
preg_match('/レジ番号:POS(\d+)/u', $text, $matches);
// $matches[1] = "1"
// 結果: "POS1"
```

#### 営業日

```php
private const BUSINESS_DATE_PATTERN = '/営業日:令和(\d+)年(\d+)月(\d+)日/u';

// マッチ例: "営業日:令和7年11月5日"
// キャプチャ: 
//   グループ1 = "7" (令和年)
//   グループ2 = "11" (月)
//   グループ3 = "5" (日)
```

**テスト**:
```php
$text = "営業日:令和7年11月5日";
preg_match('/営業日:令和(\d+)年(\d+)月(\d+)日/u', $text, $matches);
// $matches[1] = "7"
// $matches[2] = "11"
// $matches[3] = "5"
// 変換: 2025-11-05
```

#### 出力日時

```php
private const OUTPUT_DATETIME_PATTERN = '/出力日時:令和(\d+)年(\d+)月(\d+)日\s+(\d+)時(\d+)分/u';

// マッチ例: "出力日時:令和7年11月6日 17時30分"
// キャプチャ:
//   グループ1 = "7" (令和年)
//   グループ2 = "11" (月)
//   グループ3 = "6" (日)
//   グループ4 = "17" (時)
//   グループ5 = "30" (分)
```

### 4.2 商品データ部分

#### 2カラム形式の行

```php
private const ITEM_LINE_PATTERN = '/(P\d{3})\s+(.+?)\s+¥([\d,]+)\s+(\d+)\s+¥([\d,]+)\s+(P\d{3})\s+(.+?)\s+¥([\d,]+)\s+(\d+)\s+¥([\d,]+)/u';

// マッチ例:
// "P001 南部鉄器 急須(小) ¥8,500 1 ¥8,500 P016 わら細工 鍋敷き ¥800 0 ¥0"
//
// 左カラム（グループ1〜5）:
//   グループ1 = "P001" (商品コード)
//   グループ2 = "南部鉄器 急須(小)" (商品名)
//   グループ3 = "8,500" (単価)
//   グループ4 = "1" (数量)
//   グループ5 = "8,500" (小計)
//
// 右カラム（グループ6〜10）:
//   グループ6 = "P016" (商品コード)
//   グループ7 = "わら細工 鍋敷き" (商品名)
//   グループ8 = "800" (単価)
//   グループ9 = "0" (数量)
//   グループ10 = "0" (小計)
```

**重要ポイント**:
- `.+?` で商品名を非貪欲にマッチ（次の `¥` まで）
- `\s+` で空白（スペースまたはタブ）を許容

### 4.3 フッター部分

#### 合計金額

```php
private const TOTAL_PATTERN = '/合計\s+¥([\d,]+)/u';

// マッチ例: "合計 ¥89,910"
// キャプチャ: グループ1 = "89,910"
```

---

## 5. 実装コード例

### 5.1 ヘッダー解析メソッド

```php
/**
 * レジ番号を抽出
 */
private function parseRegisterId(string $text): string
{
    if (preg_match(self::REGISTER_PATTERN, $text, $matches)) {
        return 'POS' . $matches[1];
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
```

### 5.2 商品データ解析メソッド

```php
/**
 * 商品データを抽出
 */
private function parseItems(string $text): array
{
    $items = [];
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
```

### 5.3 フッター解析メソッド

```php
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
```

### 5.4 データ検証メソッド

```php
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
    // 注: $items は数量 > 0 のみを含むため、全商品データで検証する場合は
    // parseItems() で全商品を取得してから検証
    $allItemsCount = $this->countAllItems();
    if ($allItemsCount !== 30) {
        Log::warning('PosRegisterParser: 商品数が30品目ではありません', [
            'expected' => 30,
            'actual' => $allItemsCount,
        ]);
    }
}

/**
 * 全商品数をカウント（数量0を含む）
 */
private function countAllItems(): int
{
    // PDFテキストから全商品行をカウント
    // 実装例: 商品コードパターンのマッチ数をカウント
    return $this->allItemsCount ?? 0;
}
```

---

## 6. テスト実装

### 6.1 ユニットテスト

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Parsers;

use App\Services\Parsers\PosRegisterParser;
use InvalidArgumentException;
use Tests\TestCase;

class PosRegisterParserTest extends TestCase
{
    private PosRegisterParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new PosRegisterParser();
    }

    public function test_can_parse_returns_true_for_valid_format(): void
    {
        $text = <<<'TEXT'
レジ番号:POS1
営業日:令和7年11月5日
TEXT;

        expect($this->parser->canParse($text))->toBeTrue();
    }

    public function test_can_parse_returns_false_for_invalid_format(): void
    {
        $text = 'Invalid format';

        expect($this->parser->canParse($text))->toBeFalse();
    }

    public function test_parse_extracts_header_correctly(): void
    {
        $text = $this->getSamplePdfText();

        $result = $this->parser->parse($text);

        expect($result['register_id'])->toBe('POS1');
        expect($result['business_date'])->toBe('2025-11-05');
        expect($result['output_datetime'])->toBe('2025-11-06 17:30:00');
    }

    public function test_parse_extracts_items_correctly(): void
    {
        $text = $this->getSamplePdfText();

        $result = $this->parser->parse($text);

        // 数量 > 0 の商品のみ抽出されていることを確認
        expect($result['items'])->toHaveCount(18);

        // 最初の商品を検証
        $firstItem = $result['items'][0];
        expect($firstItem['product_code'])->toBe('P001');
        expect($firstItem['product_name'])->toBe('南部鉄器 急須(小)');
        expect($firstItem['unit_price'])->toBe(8500);
        expect($firstItem['quantity'])->toBe(1);
        expect($firstItem['subtotal'])->toBe(8500);
    }

    public function test_parse_extracts_total_correctly(): void
    {
        $text = $this->getSamplePdfText();

        $result = $this->parser->parse($text);

        expect($result['total'])->toBe(89910);
    }

    public function test_parse_throws_exception_when_register_id_not_found(): void
    {
        $text = '営業日:令和7年11月5日';

        expect(fn() => $this->parser->parse($text))
            ->toThrow(InvalidArgumentException::class, 'レジ番号が見つかりませんでした');
    }

    public function test_parse_throws_exception_when_no_items_found(): void
    {
        $text = <<<'TEXT'
レジ番号:POS1
営業日:令和7年11月5日
出力日時:令和7年11月6日 17時30分
合計 ¥0
TEXT;

        expect(fn() => $this->parser->parse($text))
            ->toThrow(InvalidArgumentException::class, '商品データが見つかりませんでした');
    }

    private function getSamplePdfText(): string
    {
        // 実際のサンプルPDFテキストを返す
        return file_get_contents(base_path('tests/Fixtures/sample_pos1.txt'));
    }
}
```

### 6.2 統合テスト

```php
<?php

declare(strict_types=1);

use App\Services\PdfReader;
use App\Services\Parsers\PosRegisterParser;

test('PDF to Parse integration', function () {
    $pdfPath = base_path('tests/Fixtures/sample_pos1.pdf');

    // 1. PDFからテキストを抽出
    $pdfReader = new PdfReader();
    $text = $pdfReader->extract($pdfPath);

    // 2. テキストをパース
    $parser = new PosRegisterParser();
    $result = $parser->parse($text);

    // 3. 結果を検証
    expect($result)->toHaveKeys([
        'register_id',
        'business_date',
        'output_datetime',
        'items',
        'total',
    ]);

    expect($result['items'])->not->toBeEmpty();
    expect($result['total'])->toBeGreaterThan(0);
});
```

---

## 7. デバッグ方法

### 7.1 ログ出力

```php
Log::info('PosRegisterParser: PDFテキスト', [
    'text_length' => strlen($text),
    'first_100_chars' => substr($text, 0, 100),
]);

Log::info('PosRegisterParser: 正規表現マッチ', [
    'pattern' => self::ITEM_LINE_PATTERN,
    'line' => $line,
    'matched' => (bool) preg_match(self::ITEM_LINE_PATTERN, $line),
]);
```

### 7.2 中間データの確認

```php
// パース途中のデータをダンプ
dd([
    'register_id' => $registerId,
    'business_date' => $businessDate,
    'items' => $items,
]);
```

### 7.3 正規表現のテスト

オンラインツールを使用:
- [Regex101](https://regex101.com/)
- [RegExr](https://regexr.com/)

---

## 8. トラブルシューティング

### 8.1 商品名がうまく抽出できない

**原因**: 商品名に特殊文字や空白が含まれている

**解決策**:
```php
// 正規表現を調整
private const ITEM_LINE_PATTERN = '/(P\d{3})\s+(.+?)\s+¥([\d,]+)\s+(\d+)\s+¥([\d,]+)/u';
// .+? を使用して非貪欲マッチ
```

### 8.2 2カラム形式の右側が取得できない

**原因**: カラム間の区切り文字（空白数）が不定

**解決策**:
```php
// \s+ で複数の空白を許容
private const ITEM_LINE_PATTERN = '/(P\d{3})\s+(.+?)\s+¥([\d,]+)\s+(\d+)\s+¥([\d,]+)\s+(P\d{3})\s+(.+?)\s+¥([\d,]+)\s+(\d+)\s+¥([\d,]+)/u';
```

### 8.3 令和年の変換がうまくいかない

**原因**: 令和元年のハンドリング

**解決策**:
```php
private function convertReiwaToYear(string $reiwaYearString): int
{
    if ($reiwaYearString === '元') {
        return 2019;
    }

    $reiwaYear = (int) $reiwaYearString;
    return 2018 + $reiwaYear;
}
```

### 8.4 合計金額が一致しない

**原因**: 数量0の商品を含めて計算している

**解決策**:
```php
// 数量 > 0 の商品のみを集計
$calculatedTotal = array_sum(
    array_column(
        array_filter($items, fn($item) => $item['quantity'] > 0),
        'subtotal'
    )
);
```

### 8.5 商品数が30品目より少ない

**原因**: PDFデータの一部が欠損している、または正規表現が一部の商品にマッチしていない

**デバッグ方法**:
```php
// 全商品コードを抽出
preg_match_all('/(P\d{3})/', $text, $allProductCodes);
Log::info('全商品コード', [
    'count' => count($allProductCodes[1]),
    'codes' => $allProductCodes[1],
]);

// 期待される商品コード（P001〜P030）
$expectedCodes = array_map(fn($i) => sprintf('P%03d', $i), range(1, 30));
$missingCodes = array_diff($expectedCodes, $allProductCodes[1]);

if (!empty($missingCodes)) {
    Log::warning('欠損している商品コード', [
        'missing' => $missingCodes,
    ]);
}
```

### 8.6 PDFフォーマットの確認

**すべてのレジが同じフォーマットか確認する方法**:

```php
// 各レジのPDFをパースして、構造を比較
$registers = ['POS1', 'POS2', 'POS3', 'POS4'];
$structures = [];

foreach ($registers as $registerId) {
    $pdfPath = "path/to/{$registerId}.pdf";
    $text = $pdfReader->extract($pdfPath);
    
    $structures[$registerId] = [
        'has_register_pattern' => preg_match(self::REGISTER_PATTERN, $text) === 1,
        'has_business_date' => preg_match(self::BUSINESS_DATE_PATTERN, $text) === 1,
        'item_count' => preg_match_all(self::ITEM_LINE_PATTERN, $text),
        'has_total' => preg_match(self::TOTAL_PATTERN, $text) === 1,
    ];
}

Log::info('レジフォーマット比較', $structures);
```

---

## 9. パフォーマンス最適化

### 9.1 正規表現のコンパイル

```php
// パターンを事前にコンパイル
private static array $compiledPatterns = [];

private function getCompiledPattern(string $pattern): string
{
    if (!isset(self::$compiledPatterns[$pattern])) {
        self::$compiledPatterns[$pattern] = $pattern;
    }

    return self::$compiledPatterns[$pattern];
}
```

### 9.2 大量データの処理

```php
// ストリーミング処理
private function parseItemsStream(string $text): \Generator
{
    $lines = explode("\n", $text);

    foreach ($lines as $line) {
        if (preg_match(self::ITEM_LINE_PATTERN, $line, $matches)) {
            yield $this->buildItem(/* ... */);
        }
    }
}
```

---

## 10. 更新履歴

| 日付 | バージョン | 変更内容 | 作成者 |
|------|-----------|---------|-------|
| 2025-11-15 | 1.0.0 | 初版作成 | - |

---

**END OF DOCUMENT**

