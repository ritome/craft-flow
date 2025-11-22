# 📋 テンプレートベースExcel生成 実装計画

## 実装日
2025-11-21

## 概要

完成例の精算書テンプレート（Googleスプレッドシート）を使用して、
現在の動的Excel生成方式から「テンプレート読み込み→値埋め込み」方式に移行します。

## 📁 作成・変更ファイル一覧

### 新規作成（3ファイル）

1. ✅ **`app/Support/Excel/SettlementTemplateCells.php`**
   - セル座標定数クラス
   - テンプレート内のセル座標を一元管理
   - マジックナンバー排除

2. ✅ **`app/Services/Settlement/SettlementTemplateService.php`**
   - テンプレート処理サービス
   - テンプレート読み込み
   - セルへのデータ書き込み
   - 明細行の動的追加

3. ⏳ **`resources/excel/settlement_template.xlsx`** 【要対応】
   - **完成例のテンプレートファイルをここに配置してください**
   - Googleスプレッドシートからダウンロードした .xlsx ファイル
   - https://docs.google.com/spreadsheets/d/1ZHWR6fwZMQh0bxZetfdSERRdbSSQhFwA9tQkQyEh_9o/edit?gid=1414744764#gid=1414744764

### 修正（2ファイル）

4. ✅ **`app/Exports/SettlementExcelExport.php`**
   - テンプレートサービスを使用するように修正
   - SettlementClientSheetクラスを簡素化

5. ⏳ **`app/Services/SettlementService.php`**
   - `generateFiles()` メソッドを修正
   - テンプレートベースの生成に変更

## 🎯 実装方針の変更

### Before（現在）

```php
// 動的生成方式
Excel::store(
    new SettlementExcelExport($settlement, $settlementData),
    $excelPath,
    'local'
);
```

- PHPコードで動的にExcelを生成
- セル結合、色、罫線などをすべてコードで指定
- レイアウト変更のたびにコード修正が必要

### After（新方式）

```php
// テンプレートベース方式
foreach ($settlementData as $clientCode => $clientData) {
    // 1. テンプレート読み込み
    $spreadsheet = $templateService->loadTemplate();
    
    // 2. データ書き込み
    $templateService->fillTemplate($spreadsheet, $settlement, $clientData);
    
    // 3. ファイル保存
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save($filePath);
}

// 4. 複数ファイルをZIPにまとめる
```

## ✅ 実装済み内容

### 1. セル座標定数クラス

**ファイル:** `app/Support/Excel/SettlementTemplateCells.php`

**定義内容:**
```php
// ヘッダー部
public const SETTLEMENT_NUMBER = 'H3';
public const ISSUE_DATE = 'H4';

// 委託先情報
public const CLIENT_NAME = 'A9';
public const CLIENT_ADDRESS = 'A11';
public const BILLING_PERIOD = 'A13';

// お支払金額ボックス
public const PAYMENT_AMOUNT = 'E10';

// 明細テーブル
public const DETAIL_START_ROW = 15;
public const DETAIL_COL_CODE = 'A';
public const DETAIL_COL_NAME = 'B';
// ...
```

**注意:** 実際のセル座標は、テンプレートファイルを開いて確認後、調整が必要です。

### 2. テンプレート処理サービス

**ファイル:** `app/Services/Settlement/SettlementTemplateService.php`

**主要メソッド:**
- `loadTemplate()` - テンプレート読み込み
- `fillTemplate()` - データ書き込み
- `fillHeader()` - ヘッダー情報
- `fillClientInfo()` - 委託先情報
- `fillAmountBoxes()` - 金額ボックス
- `fillDetails()` - 商品明細（動的行追加）
- `fillTotals()` - 集計行
- `fillBankInfo()` - 振込先情報

### 3. Export クラスの修正

**ファイル:** `app/Exports/SettlementExcelExport.php`

- `SettlementTemplateService` をインジェクション
- `SettlementClientSheet` をテンプレート方式に変更

## 🚧 未完了作業

### 【重要】テンプレートファイルの配置

#### 手順

1. **Googleスプレッドシートから完成例をダウンロード**
   
   - URL: https://docs.google.com/spreadsheets/d/1ZHWR6fwZMQh0bxZetfdSERRdbSSQhFwA9tQkQyEh_9o/edit?gid=1414744764#gid=1414744764
   - ファイル → ダウンロード → Microsoft Excel (.xlsx)

2. **プロジェクトに配置**
   
   ```bash
   # ダウンロードしたファイルを移動
   mv ~/Downloads/[ファイル名].xlsx resources/excel/settlement_template.xlsx
   ```

3. **ファイルを開いてセル座標を確認**
   
   - Microsoft Excel または LibreOffice Calc で開く
   - 各項目がどのセルに配置されているか確認
   - `app/Support/Excel/SettlementTemplateCells.php` の定数を調整

#### 確認すべきセル座標

| 項目 | 現在の定数 | 実際の位置 |
|------|-----------|----------|
| タイトル | - | ? |
| 精算番号 | H3 | ? |
| 発行日 | H4 | ? |
| 請求期間 | A13 | ? |
| 委託先名 | A9 | ? |
| 委託先住所 | A11 | ? |
| お支払金額 | E10 | ? |
| 明細開始行 | 15 | ? |
| 商品コード列 | A | ? |
| 商品名列 | B | ? |
| 単価列 | C | ? |
| 販売数列 | D | ? |
| 売上金額列 | E | ? |

### セル座標の調整

テンプレートファイルを確認後、必要に応じて `SettlementTemplateCells.php` の定数を修正してください。

**例:**
```php
// 実際の座標が違う場合
public const SETTLEMENT_NUMBER = 'J2';  // H3 → J2 に変更
public const ISSUE_DATE = 'J3';         // H4 → J3 に変更
```

### SettlementService の修正

`generateFiles()` メソッドを修正して、テンプレートベースの生成に変更します。

**修正箇所:** `app/Services/SettlementService.php`の418-480行目付近

**修正内容:**

```php
private function generateFiles(Settlement $settlement, array $settlementData): void
{
    $dateStr = $settlement->billing_start_date->format('Ymd').'_'.$settlement->billing_end_date->format('Ymd');
    $storageDir = storage_path('app/settlements');
    
    if (! file_exists($storageDir)) {
        mkdir($storageDir, 0755, true);
    }
    
    // テンプレートサービスをインスタンス化
    $templateService = new \App\Services\Settlement\SettlementTemplateService();
    
    try {
        // 委託先ごとにExcelファイルを生成
        $excelFiles = [];
        
        foreach ($settlementData as $clientCode => $clientData) {
            // テンプレートを読み込む
            $spreadsheet = $templateService->loadTemplate();
            
            // データを書き込む
            $templateService->fillTemplate($spreadsheet, $settlement, $clientData);
            
            // ファイルを保存
            $clientName = $clientData['client_name'];
            $fileName = "{$clientName}_{$dateStr}_精算書.xlsx";
            $filePath = $storageDir . '/' . $fileName;
            
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($filePath);
            
            $excelFiles[] = $filePath;
            
            \Log::info("Excel file generated for client", [
                'client_code' => $clientCode,
                'file' => $fileName,
            ]);
        }
        
        // 複数ファイルをZIPにまとめる（元の実装を維持）
        if (count($excelFiles) > 1) {
            $zipPath = $storageDir . "/settlement_{$dateStr}_{$settlement->id}.zip";
            $this->createZipArchive($excelFiles, $zipPath);
            
            // 個別ファイルを削除
            foreach ($excelFiles as $file) {
                @unlink($file);
            }
            
            $settlement->excel_path = "settlements/settlement_{$dateStr}_{$settlement->id}.zip";
        } else {
            // 1ファイルの場合はそのまま
            $settlement->excel_path = "settlements/" . basename($excelFiles[0]);
        }
        
        \Log::info("Excel files generation completed");
        
    } catch (\Exception $e) {
        \Log::error("Excel generation error: {$e->getMessage()}");
        \Log::error("Stack trace: " . $e->getTraceAsString());
        throw new \Exception("Excel ファイルの生成に失敗しました: {$e->getMessage()}");
    }
    
    // PDF生成は既存のまま（必要に応じて後で対応）
    // ...
}

/**
 * ZIPアーカイブを作成
 */
private function createZipArchive(array $files, string $zipPath): void
{
    $zip = new \ZipArchive();
    
    if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
        throw new \Exception("ZIPファイルの作成に失敗しました");
    }
    
    foreach ($files as $file) {
        $zip->addFile($file, basename($file));
    }
    
    $zip->close();
}
```

## 🧪 テスト計画

### 1. ユニットテスト

```bash
./vendor/bin/sail artisan test --filter=SettlementTemplateService
```

### 2. 機能テスト

```bash
./vendor/bin/sail artisan test --filter=SettlementGenerationTest
```

### 3. 実際のデータでテスト

1. `/settlements` にアクセス
2. 請求期間を設定
3. 顧客マスタと売上データをアップロード
4. 「精算書を生成する」をクリック
5. Excelファイルをダウンロードして確認

### 4. 確認ポイント

✅ **レイアウト:**
- タイトル、ヘッダー、委託先情報が正しい位置に表示
- お支払金額ボックスが黄色で右上に表示
- 売上金額ボックスが右側に表示

✅ **データ:**
- 精算番号、発行日が正しい
- 委託先情報が正しい
- 商品明細が正しく表示（個別レコード）
- 金額計算が正しい

✅ **スタイル:**
- セル結合が保持されている
- 色、罫線、フォントが保持されている
- 列幅、行高が適切

## 📋 チェックリスト

### Phase 1: 準備
- [x] セル座標定数クラス作成
- [x] テンプレート処理サービス作成
- [x] Exportクラス修正
- [ ] **テンプレートファイル配置** 【要対応】
- [ ] **セル座標の確認と調整** 【要対応】

### Phase 2: SettlementService修正
- [ ] `generateFiles()` メソッド修正
- [ ] ZIP作成ロジックの追加
- [ ] エラーハンドリングの実装

### Phase 3: テスト
- [ ] リンターエラー確認
- [ ] 既存テスト実行
- [ ] 実際のデータでテスト
- [ ] 完成例との比較

### Phase 4: ドキュメント
- [ ] `docs/excel_layout_settlement_format.md` 更新
- [ ] セル座標マッピング表の作成
- [ ] README更新（必要に応じて）

## 🚨 重要な注意事項

### 1. テンプレートファイルは必須

実装は完了していますが、**テンプレートファイルがないと動作しません**。

エラーメッセージ:
```
テンプレートファイルが見つかりません: resources/excel/settlement_template.xlsx

完成例の精算書Excelを resources/excel/settlement_template.xlsx として配置してください。
```

### 2. セル座標の調整が必要

現在の定数は推測値です。実際のテンプレートを確認して調整してください。

### 3. 既存テストへの影響

テスト内で「セル値」を検証している場合、座標の変更が必要です。

### 4. PDF生成について

現時点では **Excel版のみ** がテンプレート方式に対応します。
PDF版は後で対応する予定です。

## 📚 関連ドキュメント

- `docs/TEMPLATE_BASED_EXCEL_DESIGN.md` - 設計書
- `docs/excel_layout_settlement_format.md` - Excelレイアウト仕様
- `docs/settlement_detail_specification.md` - 精算書明細仕様

## 🎯 次のアクション

### 【最優先】テンプレートファイルの配置

1. Googleスプレッドシートから完成例をダウンロード
2. `resources/excel/settlement_template.xlsx` として配置
3. セル座標を確認
4. `SettlementTemplateCells.php` を調整

### 実装の完了

1. `SettlementService::generateFiles()` を修正
2. テストを実行
3. 実際のデータで確認

---

**作成日:** 2025-11-21  
**担当:** AI Assistant  
**ステータス:** 🚧 実装中（テンプレートファイル待ち）

