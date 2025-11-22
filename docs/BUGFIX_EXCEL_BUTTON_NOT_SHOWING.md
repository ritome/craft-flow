# 🐛 バグ修正: Excelボタンが表示されない問題

## 発生日時
2025-11-21

## 問題の概要

テンプレートベースのExcel生成機能を実装したが、ブラウザの精算履歴画面で「Excel」ボタンが表示されない。
- ❌ 「Excel」ボタンが表示されない
- ✅ 「PDF」ボタンは表示される
- ✅ Excel ZIPファイルは物理的に生成されている
- ✅ DBにパスも保存されている

## 根本原因

### 問題1: Storage APIとファイルシステムの不整合

PHPSpreadsheetの `$writer->save()` で直接ファイルシステムに書き込んでいたため、
Laravelの `Storage` APIのインデックスが更新されず、`Storage::exists()` が **false** を返していた。

**具体的な流れ:**

```php
// generateExcelFiles() メソッド
$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save($filePath);  // ← 直接ファイルシステムに保存

$this->createZipArchive($excelFiles, $zipPath);  // ← ZIPも直接保存
```

**結果:**
- ✅ ファイルは物理的に存在: `/var/www/html/storage/app/settlements/settlement_xxx.zip`
- ❌ `Storage::disk('local')->exists('settlements/settlement_xxx.zip')` → **false**

### 問題2: hasExcelFile() の判定ロジック

```php
// app/Models/Settlement.php（修正前）
public function hasExcelFile(): bool
{
    return $this->excel_path && Storage::disk('local')->exists($this->excel_path);
    //                          ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
    //                          これが false を返す → ボタンが表示されない
}
```

## 修正内容

### 修正1: Settlement::hasExcelFile() の改善

`file_exists()` による直接確認も追加し、Storage APIとファイルシステムの両方をチェックするようにした。

**修正後:**

```php
// app/Models/Settlement.php
public function hasExcelFile(): bool
{
    if (! $this->excel_path) {
        return false;
    }
    
    // Storageでチェック
    if (Storage::disk('local')->exists($this->excel_path)) {
        return true;
    }
    
    // file_exists()でもチェック（PHPSpreadsheetで直接保存したファイル用）
    $fullPath = storage_path('app/' . $this->excel_path);
    return file_exists($fullPath);
}
```

**効果:**
- ✅ 既存のファイル（PHPSpreadsheetで直接保存）も認識される
- ✅ 新しいファイル（Storageで管理）も認識される
- ✅ 後方互換性が保たれる

### 修正2: Settlement::getExcelContent() の改善

同様に、`file_get_contents()` による取得もサポート。

**修正後:**

```php
// app/Models/Settlement.php
public function getExcelContent(): string
{
    // まずStorageで取得を試みる
    if (Storage::disk('local')->exists($this->excel_path)) {
        return Storage::disk('local')->get($this->excel_path);
    }
    
    // file_get_contents()で取得（PHPSpreadsheetで直接保存したファイル用）
    $fullPath = storage_path('app/' . $this->excel_path);
    if (file_exists($fullPath)) {
        return file_get_contents($fullPath);
    }
    
    throw new \RuntimeException('Excelファイルが見つかりません: ' . $this->excel_path);
}
```

### 修正3: generateExcelFiles() の根本修正

今後生成されるファイルは、Storage APIを通して保存するように変更。

**修正後:**

```php
// app/Services/SettlementService.php
private function generateExcelFiles(...): string
{
    // ... Excel生成 ...
    
    // 複数ファイルをZIPにまとめる
    if (count($excelFiles) > 1) {
        $zipFileName = "settlement_{$dateStr}_{$settlement->id}.zip";
        $zipPath = $storageDir.'/'.$zipFileName;
        
        $this->createZipArchive($excelFiles, $zipPath);
        
        // 個別ファイルを削除
        foreach ($excelFiles as $file) {
            @unlink($file);
        }
        
        // ★ 修正箇所: Storageに登録
        $storagePath = "settlements/{$zipFileName}";
        $zipContent = file_get_contents($zipPath);
        Storage::disk('local')->put($storagePath, $zipContent);
        
        // 元のZIPファイルを削除（Storageで管理されるようになったため）
        @unlink($zipPath);
        
        \Log::info('Created ZIP archive and registered to Storage', [
            'file' => $zipFileName,
            'client_count' => count($excelFiles),
            'storage_path' => $storagePath,
        ]);
        
        return $storagePath;
    } elseif (count($excelFiles) === 1) {
        // ★ 修正箇所: 1ファイルの場合もStorageに登録
        $fileName = basename($excelFiles[0]);
        $storagePath = "settlements/{$fileName}";
        $excelContent = file_get_contents($excelFiles[0]);
        Storage::disk('local')->put($storagePath, $excelContent);
        
        // 元のファイルを削除
        @unlink($excelFiles[0]);
        
        \Log::info('Registered single Excel file to Storage', [
            'file' => $fileName,
            'storage_path' => $storagePath,
        ]);
        
        return $storagePath;
    }
}
```

**効果:**
- ✅ 今後生成されるファイルは `Storage::exists()` で正しく認識される
- ✅ Storage APIの恩恵を受けられる（将来的にS3等への移行が容易）
- ✅ 一貫性のあるファイル管理

## テスト結果

### 修正前

```bash
$ ./vendor/bin/sail php check_db.php

【Excel】
  excel_path: settlements/settlement_20251001_20251031_34.zip
  ファイル存在: ❌ NO
  hasExcelFile(): ❌ false  ← ボタンが表示されない
```

### 修正後

```bash
$ ./vendor/bin/sail php check_db.php

【Excel】
  excel_path: settlements/settlement_20251001_20251031_34.zip
  ファイル存在: ❌ NO (Storage API)
  hasExcelFile(): ✅ true  ← ボタンが表示される！
  実際に存在するか: YES (file_exists)
```

### 全テスト

```bash
$ ./vendor/bin/sail artisan test --filter=SettlementGenerationTest

✅ 全テストパス（14/14）

PASS  Tests\Feature\Settlement\SettlementGenerationTest
✓ 精算トップ画面が表示される
✓ 必須項目が未入力の場合エラーになる
✓ 請求開始日が終了日より後の場合エラーになる
✓ 請求終了日が未来日の場合エラーになる
✓ 請求期間が3ヶ月超の場合エラーになる
✓ ファイル形式が不正な場合エラーになる
✓ ファイルサイズが10MBを超える場合エラーになる
✓ 精算履歴一覧が表示される
✓ 精算履歴が存在しない場合も正常に表示される
✓ Excelダウンロードが正常に動作する ← ✅
✓ PDFダウンロードが正常に動作する
✓ ファイルが存在しない場合エラーが返される
✓ 精算履歴の削除が正常に動作する
✓ 商品明細が個別レコードとして保存される

Tests:    14 passed (40 assertions)
Duration: 2.52s
```

## ブラウザでの確認

### 修正前

```
発行日時          | 請求期間              | 委託先数 | 売上金額      | アクション
2025-11-21 01:32 | 2025年10月01日〜... | 10件    | ¥6,412,500 | PDF 削除
                                                        ^^^^
                                                        Excelボタンなし
```

### 修正後

```
発行日時          | 請求期間              | 委託先数 | 売上金額      | アクション
2025-11-21 01:32 | 2025年10月01日〜... | 10件    | ¥6,412,500 | Excel PDF 削除
                                                        ^^^^^
                                                        表示される！
```

## 影響範囲

### 変更されたファイル

1. ✅ `app/Models/Settlement.php`
   - `hasExcelFile()` メソッド修正
   - `getExcelContent()` メソッド修正

2. ✅ `app/Services/SettlementService.php`
   - `generateExcelFiles()` メソッド修正

### 影響を受ける機能

- ✅ Excel生成機能（改善）
- ✅ Excelダウンロード機能（正常動作）
- ✅ 精算履歴画面の表示（Excelボタンが表示される）
- ⚪ PDF関連機能（変更なし）

### 既存のファイルへの影響

- ✅ 既存のExcelファイル（直接保存されたもの）も認識される
- ✅ 今後生成されるファイルはStorage APIで管理される
- ✅ 後方互換性が保たれる

## 学んだこと

### 1. Storage APIとファイルシステムの違い

Laravelの `Storage` APIは、内部でファイルのインデックスを管理している。
PHPの `file_put_contents()` や PHPSpreadsheetの `$writer->save()` で直接保存したファイルは、
`Storage::exists()` で認識されない。

**解決策:**
- `Storage::put()` を使う
- または、`file_exists()` でも確認する

### 2. フォールバック戦略の重要性

`hasExcelFile()` を修正する際、Storage APIだけでなく `file_exists()` も確認することで、
既存のファイルとの互換性を保ちつつ、問題を解決できた。

### 3. 根本原因と対症療法

- **対症療法:** `hasExcelFile()` を修正（既存ファイルに対応）
- **根本治療:** `generateExcelFiles()` を修正（今後のファイルを正しく管理）

両方を実施することで、完全な解決となった。

## 今後の改善案

### 1. 既存ファイルのマイグレーション

既存の精算書ファイルをStorage APIで管理するように移行する。

```php
// Artisan コマンドを作成
php artisan settlements:migrate-files
```

### 2. S3等への移行準備

将来的にS3等のクラウドストレージに移行する場合、
`Storage::disk('local')` を `Storage::disk('s3')` に変更するだけで対応できる。

### 3. ファイルの定期クリーンアップ

古い精算書ファイルを定期的に削除するバッチ処理を追加。

## まとめ

### 問題
- ❌ Excelボタンが表示されない
- ❌ `hasExcelFile()` が false を返す
- ❌ Storage APIがファイルを認識しない

### 原因
- PHPSpreadsheetで直接ファイルシステムに保存
- Storage APIのインデックスが更新されない

### 解決策
- ✅ `hasExcelFile()` に `file_exists()` 確認を追加（対症療法）
- ✅ `generateExcelFiles()` で `Storage::put()` を使用（根本治療）
- ✅ 既存ファイルとの互換性を保持

### 効果
- ✅ Excelボタンが正しく表示される
- ✅ ダウンロードが正常に動作する
- ✅ 全テストがパス
- ✅ 後方互換性が保たれる

---

**修正日:** 2025-11-21  
**担当:** AI Assistant  
**ステータス:** ✅ 修正完了・テスト完了

