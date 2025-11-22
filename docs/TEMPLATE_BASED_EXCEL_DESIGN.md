# 📋 テンプレートベースExcel生成への移行設計書

## 作成日
2025-11-21

## 目的

現在の「動的Excel生成方式」から、「テンプレート読み込み→値埋め込み方式」に変更し、
完成例の精算書と全く同じレイアウトを実現する。

## 変更方針

### Before（現在の実装）
- PHPコードで動的にExcelを生成
- セル結合、色、罫線などをすべてコードで指定
- レイアウト変更のたびにコード修正が必要

### After（新しい実装）
- `resources/excel/settlement_template.xlsx` をテンプレートとして使用
- テンプレートを読み込み、データセルのみに値を書き込む
- デザイン変更はExcelファイルを編集するだけで対応可能

## アーキテクチャ

### 1. ディレクトリ構造

```
resources/
└── excel/
    └── settlement_template.xlsx  ← 完成例のテンプレート

app/
├── Support/
│   └── Excel/
│       └── SettlementTemplateCells.php  ← セル座標定数クラス（NEW）
├── Services/
│   └── Settlement/
│       └── SettlementTemplateService.php  ← テンプレート処理サービス（NEW）
└── Exports/
    └── SettlementExcelExport.php  ← 修正
```

### 2. セルマッピング定数クラス

**ファイル:** `app/Support/Excel/SettlementTemplateCells.php`

**目的:**
- テンプレート内のセル座標を一元管理
- マジックナンバーを排除
- 保守性の向上

**定義内容:**
```php
class SettlementTemplateCells
{
    // ヘッダー部
    public const SETTLEMENT_NUMBER = 'J2';    // 精算番号
    public const ISSUE_DATE = 'J3';           // 発行日
    
    // 委託先情報
    public const CLIENT_NAME = 'B16';         // 委託先名
    public const CLIENT_ADDRESS = 'B17';      // 委託先住所
    public const BILLING_PERIOD = 'B14';      // 請求期間
    
    // 金額ボックス
    public const PAYMENT_AMOUNT = 'F8';       // お支払金額（黄色ボックス）
    public const SALES_TOTAL = 'I18';         // 売上金額（右側ボックス）
    
    // 明細テーブル
    public const DETAIL_START_ROW = 22;       // 明細開始行
    public const DETAIL_COL_CODE = 'B';       // 商品コード列
    public const DETAIL_COL_NAME = 'C';       // 商品名列
    public const DETAIL_COL_PRICE = 'D';      // 単価列
    public const DETAIL_COL_QTY = 'E';        // 販売数列
    public const DETAIL_COL_AMOUNT = 'F';     // 金額列
    
    // 集計行
    public const SUBTOTAL = 'F74';            // 小計
    public const SETTLEMENT_TOTAL = 'F76';    // 精算額合計
    public const TRANSFER_AMOUNT = 'F80';     // 振込金額
    
    // 振込先情報
    public const BANK_INFO = 'B85';           // 銀行情報（結合セル）
}
```

### 3. テンプレート処理サービス

**ファイル:** `app/Services/Settlement/SettlementTemplateService.php`

**責務:**
- テンプレートファイルの読み込み
- セルへのデータ書き込み
- 明細行の動的追加
- ファイルの保存

**主要メソッド:**
```php
class SettlementTemplateService
{
    // テンプレートを読み込んで Spreadsheet オブジェクトを取得
    public function loadTemplate(): Spreadsheet
    
    // 精算データをテンプレートに書き込み
    public function fillTemplate(
        Spreadsheet $spreadsheet,
        Settlement $settlement,
        array $clientData
    ): void
    
    // ヘッダー情報の書き込み
    private function fillHeader(Worksheet $sheet, Settlement $settlement): void
    
    // 委託先情報の書き込み
    private function fillClientInfo(Worksheet $sheet, array $clientData, Settlement $settlement): void
    
    // 金額ボックスの書き込み
    private function fillAmountBoxes(Worksheet $sheet, array $clientData): void
    
    // 商品明細の書き込み（動的行追加）
    private function fillDetails(Worksheet $sheet, array $salesDetails): void
    
    // 集計行の書き込み
    private function fillTotals(Worksheet $sheet, array $clientData): void
    
    // 振込先情報の書き込み
    private function fillBankInfo(Worksheet $sheet, array $clientData): void
}
```

### 4. Export クラスの修正

**ファイル:** `app/Exports/SettlementExcelExport.php`

**変更内容:**
- `SettlementTemplateService` を使用
- 委託先ごとにテンプレートを複製
- 動的生成コードを削除

## 実装手順

### Phase 1: 基盤の準備
1. ✅ `resources/excel/` ディレクトリ作成
2. ⏳ テンプレートファイル配置確認
3. ⏳ セル座標定数クラス作成
4. ⏳ テンプレート処理サービス作成

### Phase 2: Export クラスの修正
5. ⏳ `SettlementExcelExport` をテンプレート方式に変更
6. ⏳ `SettlementClientSheet` クラスの削除または簡素化

### Phase 3: テスト＆検証
7. ⏳ 既存テストの実行
8. ⏳ 実際の精算書生成テスト
9. ⏳ 完成例との比較確認

### Phase 4: ドキュメント更新
10. ⏳ `docs/excel_layout_settlement_format.md` 更新
11. ⏳ README 更新（必要に応じて）

## セルマッピング詳細（テンプレート解析後に更新）

### テンプレート構造

```
行  | 内容                              | セル座標
----|-----------------------------------|----------
1-2 | タイトル「委託販売精算書」         | D1:G2（結合）
3-6 | 発行元情報＋精算番号・発行日       | A3:J6
7   | 空行                              |
8-11| 委託先情報＋お支払金額ボックス     | A8:J11
12  | 空行                              |
13  | 空行                              |
14  | 請求期間                          | B14
15  | 空行                              |
16-17| 委託先名・住所                    | B16, B17
18-20| 売上金額ボックス（右側）           | I18:I20
21  | 明細ヘッダー                       | A21:F21
22~ | 商品明細（可変行数）               | A22:F22~
... | ...                               |
74  | 小計                              | F74
75  | 委託販売手数料                     | F75
76  | 精算額合計                         | F76
77  | 消費税                            | F77
78  | 振込手数料                         | F78
79  | 空行                              |
80  | お支払金額（最終）                 | F80
81~ | 振込予定日・振込先情報             | A81~
```

※ 実際のセル座標は、テンプレートファイルを開いて正確に確認する必要があります。

## データフロー

```
1. SettlementService::generateFiles()
   ↓
2. SettlementExcelExport::sheets()
   ↓
3. SettlementClientSheet (新実装)
   ├→ SettlementTemplateService::loadTemplate()
   ├→ SettlementTemplateService::fillTemplate()
   │   ├→ fillHeader()
   │   ├→ fillClientInfo()
   │   ├→ fillAmountBoxes()
   │   ├→ fillDetails()  ← 明細行を動的に追加
   │   ├→ fillTotals()
   │   └→ fillBankInfo()
   ↓
4. ファイル保存
```

## 注意事項

### 1. 明細行の動的追加

明細件数が可変のため、テンプレートの行を複製して追加する必要があります。

```php
// 22行目をコピーして23, 24, 25... と追加
for ($i = 1; $i < count($details); $i++) {
    $sheet->insertNewRowBefore($startRow + $i, 1);
    // 前の行のスタイルをコピー
}
```

### 2. Excel関数の保持

テンプレート内に計算式（SUM等）がある場合：
- セルに値を書き込む場合、式は上書きされる
- 式を保持したい場合は、値を書き込むセルと式のセルを分ける

### 3. セルの結合

テンプレート内の結合セルに値を書き込む場合：
- 結合セルの左上（最初のセル）に値を設定
- 例：`F8:G10` が結合されている場合、`F8` に書き込む

### 4. 既存テストへの影響

- テストは「ファイルが生成されること」を確認しているので、基本的に影響なし
- セル値を検証しているテストがあれば、座標を修正

## 期待される効果

### メリット

✅ **保守性の向上**
- デザイン変更が Excel ファイルの編集だけで完結
- コード修正不要

✅ **可読性の向上**
- セル座標が定数で管理され、意味が明確

✅ **品質の向上**
- 完成例と完全に一致するレイアウト
- デザインの統一性

✅ **開発効率の向上**
- デザイナーが Excel で直接編集可能
- エンジニアはデータ埋め込みロジックに集中

### デメリットと対策

❌ **テンプレートファイルの管理**
→ Git で管理し、バージョン管理

❌ **セル座標の変更リスク**
→ 定数クラスで一元管理
→ テンプレート更新時は座標も確認

❌ **動的な行追加の複雑性**
→ サービスクラスで抽象化
→ テストで動作保証

## 次のステップ

1. テンプレートファイルの配置確認
2. セル座標の正確な特定
3. 定数クラスの作成
4. テンプレートサービスの実装
5. Export クラスの修正
6. テスト実行
7. 実物確認

---

**作成日:** 2025-11-21  
**担当:** AI Assistant  
**ステータス:** 🚧 設計中

