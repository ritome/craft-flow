# PdfReader - PDF テキスト抽出サービス

## 概要

`PdfReader` は PDF ファイルからテキストを抽出するサービスクラスです。`spatie/pdf-to-text` パッケージを使用して、PDF ファイルの内容をテキスト形式で取得します。

## 依存関係

### PHP パッケージ
- `spatie/pdf-to-text`: ^1.54

### システムパッケージ
- `poppler-utils`: pdftotext コマンドを提供

## インストール

### 1. PHP パッケージのインストール

```bash
composer require spatie/pdf-to-text
```

### 2. システムパッケージのインストール (Docker Sail)

```bash
./vendor/bin/sail root-shell -c "apt-get update && apt-get install -y poppler-utils"
```

または Dockerfile に以下を追加:

```dockerfile
RUN apt-get update && apt-get install -y poppler-utils
```

## 使用方法

### 基本的な使い方

```php
use App\Services\PdfReader;

$pdfReader = new PdfReader();

try {
    $text = $pdfReader->extract('/path/to/file.pdf');
    echo $text;
} catch (InvalidArgumentException $e) {
    // ファイルが見つからない、または読み取れない
    Log::error('PDF file error: ' . $e->getMessage());
} catch (RuntimeException $e) {
    // PDFからテキストを抽出できない
    Log::error('PDF extraction error: ' . $e->getMessage());
}
```

### サービスコンテナを使用

```php
use App\Services\PdfReader;

class SomeController extends Controller
{
    public function __construct(
        private PdfReader $pdfReader
    ) {}

    public function extractPdf(Request $request)
    {
        $file = $request->file('pdf');
        $path = $file->store('pdfs');
        
        try {
            $text = $this->pdfReader->extract(storage_path('app/' . $path));
            return response()->json(['text' => $text]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
```

## API リファレンス

### `extract(string $filePath): string`

PDF ファイルからテキストを抽出します。

#### パラメータ
- `$filePath` (string): PDF ファイルのフルパス

#### 戻り値
- `string`: 抽出されたテキスト

#### 例外
- `InvalidArgumentException`: ファイルが存在しない、または読み取れない場合
- `RuntimeException`: PDF からテキストを抽出できない場合

### `extractText(string $filePath): string`

`extract()` のエイリアスメソッド（後方互換性のため）。

## エラーハンドリング

```php
use App\Services\PdfReader;
use InvalidArgumentException;
use RuntimeException;

$pdfReader = new PdfReader();

try {
    $text = $pdfReader->extract($pdfPath);
} catch (InvalidArgumentException $e) {
    // ファイルシステムエラー
    // - ファイルが存在しない
    // - ファイルの読み取り権限がない
    Log::error('File access error', [
        'path' => $pdfPath,
        'error' => $e->getMessage()
    ]);
} catch (RuntimeException $e) {
    // PDF処理エラー
    // - 破損したPDF
    // - パスワード保護されたPDF
    // - 空のPDF
    // - PDFではないファイル
    Log::error('PDF processing error', [
        'path' => $pdfPath,
        'error' => $e->getMessage()
    ]);
}
```

## テスト

### Unit テスト実行

```bash
./vendor/bin/sail artisan test --filter=PdfReaderTest
```

### Feature テスト実行（統合テスト）

```bash
./vendor/bin/sail artisan test --filter=PdfReaderIntegrationTest
```

### テストフィクスチャの準備

統合テストを実行する場合は、`tests/Fixtures/` ディレクトリに以下のサンプル PDF を配置してください：

- `sample.pdf`: 基本的なPDFファイル
- `japanese_sample.pdf`: 日本語を含むPDFファイル
- `multipage_sample.pdf`: 複数ページのPDFファイル
- `register_sample.pdf`: レジから出力されるPDFのサンプル
- `large_sample.pdf`: 大きなPDFファイル（パフォーマンステスト用）
- `corrupted.pdf`: 破損したPDF（エラーハンドリングテスト用）
- `password_protected.pdf`: パスワード保護されたPDF（エラーハンドリングテスト用）

## トラブルシューティング

### pdftotext コマンドが見つからない

```bash
# コンテナ内で確認
./vendor/bin/sail exec laravel.test which pdftotext

# インストールされていない場合
./vendor/bin/sail root-shell -c "apt-get update && apt-get install -y poppler-utils"
```

### 日本語が文字化けする

`pdftotext` は UTF-8 でテキストを出力しますが、PDF 内のエンコーディングによっては正しく抽出できない場合があります。その場合は、PDF の作成元で UTF-8 エンコーディングを使用するように設定してください。

### メモリ不足エラー

大きな PDF ファイルを処理する場合、メモリ不足エラーが発生する可能性があります。

```php
// php.ini または .env で調整
memory_limit=512M
```

または、Job キューを使用して非同期処理を行ってください。

## パフォーマンス考慮事項

- **大きなPDF**: 数十ページ以上の PDF を処理する場合は、Job キューでの非同期処理を推奨
- **バッチ処理**: 複数の PDF を処理する場合は、並列処理を検討
- **キャッシュ**: 同じ PDF を繰り返し処理する場合は、結果をキャッシュすることを検討

## セキュリティ考慮事項

1. **ファイルパスの検証**: ユーザー入力を直接ファイルパスとして使用しない
2. **ファイルサイズ制限**: アップロード時にファイルサイズを制限
3. **ファイルタイプ検証**: PDF ファイルかどうかを MIME タイプで検証
4. **一時ファイルの削除**: 処理後は一時ファイルを必ず削除

```php
// セキュアな実装例
public function processPdf(UploadedFile $file)
{
    // MIMEタイプを検証
    if ($file->getMimeType() !== 'application/pdf') {
        throw new InvalidArgumentException('PDFファイルのみアップロード可能です');
    }
    
    // ファイルサイズを制限（10MB）
    if ($file->getSize() > 10 * 1024 * 1024) {
        throw new InvalidArgumentException('ファイルサイズが大きすぎます');
    }
    
    // 安全なパスに保存
    $path = $file->store('temp/pdfs');
    $fullPath = storage_path('app/' . $path);
    
    try {
        $text = $this->pdfReader->extract($fullPath);
        return $text;
    } finally {
        // 必ず削除
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
}
```

## ライセンス

このプロジェクトは MIT ライセンスのもとで公開されています。

