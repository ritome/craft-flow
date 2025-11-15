<?php

declare(strict_types=1);

use App\Http\Controllers\PdfImportController;
use App\Services\Aggregator;
use App\Services\ExcelExporter;
use App\Services\Normalizer;
use App\Services\ParserFactory;
use App\Services\PdfImportService;
use App\Services\PdfReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    // テスト用のPDFディレクトリを作成
    if (!File::exists(storage_path('app/pdf_temp'))) {
        File::makeDirectory(storage_path('app/pdf_temp'), 0755, true);
    }
    if (!File::exists(storage_path('app/exports'))) {
        File::makeDirectory(storage_path('app/exports'), 0755, true);
    }
});

afterEach(function () {
    // テスト後のクリーンアップ
    $tempDir = storage_path('app/pdf_temp');
    if (File::exists($tempDir)) {
        File::cleanDirectory($tempDir);
    }

    $exportsDir = storage_path('app/exports');
    if (File::exists($exportsDir)) {
        File::cleanDirectory($exportsDir);
    }
});

test('PDFアップロードからExcel生成までのE2Eテスト', function () {
    // テスト用のPDFテキストデータを作成（レジAフォーマット）
    $pdfContent1 = <<<'TEXT'
========================================
レジA 売上レポート
日付: 2024/01/15
========================================
商品名             数量  単価   金額
----------------------------------------
コーヒー            2    300    600
サンドイッチ        1    500    500
----------------------------------------
合計                            1100円
========================================
TEXT;

    $pdfContent2 = <<<'TEXT'
========================================
レジA 売上レポート
日付: 2024/01/16
========================================
商品名             数量  単価   金額
----------------------------------------
コーヒー            3    300    900
紅茶                1    250    250
----------------------------------------
合計                            1150円
========================================
TEXT;

    // テスト用のPDFファイル（実際はテキスト）を作成
    $pdfPath1 = storage_path('app/pdf_temp/test_pdf_1.txt');
    $pdfPath2 = storage_path('app/pdf_temp/test_pdf_2.txt');
    file_put_contents($pdfPath1, $pdfContent1);
    file_put_contents($pdfPath2, $pdfContent2);

    // PdfReaderをモックして、PDFの代わりにテキストファイルを読む
    $mockPdfReader = mock(PdfReader::class);
    $mockPdfReader->shouldReceive('extract')
        ->with($pdfPath1)
        ->andReturn($pdfContent1);
    $mockPdfReader->shouldReceive('extract')
        ->with($pdfPath2)
        ->andReturn($pdfContent2);

    // サービスをセットアップ
    $parserFactory = new ParserFactory();
    $normalizer = new Normalizer();
    $aggregator = new Aggregator();
    $excelExporter = new ExcelExporter();

    $pdfImportService = new PdfImportService(
        $mockPdfReader,
        $parserFactory,
        $normalizer,
        $aggregator,
        $excelExporter
    );

    // サービスを実行
    $excelPath = $pdfImportService->import([$pdfPath1, $pdfPath2]);

    // アサーション
    expect($excelPath)->toBeString();
    expect(File::exists($excelPath))->toBeTrue();
    expect(pathinfo($excelPath, PATHINFO_EXTENSION))->toBe('xlsx');

    // Excelファイルのサイズが0より大きいことを確認
    expect(filesize($excelPath))->toBeGreaterThan(0);

    // クリーンアップ
    File::delete($pdfPath1);
    File::delete($pdfPath2);
    File::delete($excelPath);
})->group('e2e');

test('Controller経由でのPDFインポート処理', function () {
    // テスト用のPDFコンテンツ
    $pdfContent = <<<'TEXT'
========================================
レジA 売上レポート
日付: 2024/01/20
========================================
商品名             数量  単価   金額
----------------------------------------
コーヒー            5    300    1500
サンドイッチ        2    500    1000
----------------------------------------
合計                            2500円
========================================
TEXT;

    // 仮想のアップロードファイルを作成
    $tempFile = tempnam(sys_get_temp_dir(), 'pdf_test_');
    file_put_contents($tempFile, $pdfContent);

    $uploadedFile = new UploadedFile(
        $tempFile,
        'test_sales.pdf',
        'application/pdf',
        null,
        true
    );

    // PdfReaderをモックして、アップロードされたファイルを読み込む
    $this->mock(PdfReader::class, function ($mock) use ($pdfContent) {
        $mock->shouldReceive('extract')
            ->andReturn($pdfContent);
    });

    // Controllerに POST リクエスト
    $response = $this->post(route('pdf.import'), [
        'pdf_files' => [$uploadedFile],
    ]);

    // レスポンスがダウンロード可能なファイルであることを確認
    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    // 一時ファイルのクリーンアップ
    if (File::exists($tempFile)) {
        File::delete($tempFile);
    }
})->group('e2e', 'controller');

test('バリデーションエラー: PDFファイルが指定されていない', function () {
    $response = $this->post(route('pdf.import'), [
        'pdf_files' => [],
    ]);

    $response->assertSessionHasErrors('pdf_files');
})->group('validation');

test('バリデーションエラー: PDF以外のファイル形式', function () {
    $textFile = UploadedFile::fake()->create('test.txt', 100, 'text/plain');

    $response = $this->post(route('pdf.import'), [
        'pdf_files' => [$textFile],
    ]);

    $response->assertSessionHasErrors('pdf_files.0');
})->group('validation');

test('バリデーションエラー: ファイルサイズが大きすぎる', function () {
    // 10MBを超えるファイルを作成
    $largeFile = UploadedFile::fake()->create('large.pdf', 11000, 'application/pdf');

    $response = $this->post(route('pdf.import'), [
        'pdf_files' => [$largeFile],
    ]);

    $response->assertSessionHasErrors('pdf_files.0');
})->group('validation');

test('複数PDFファイルの集計処理', function () {
    // 複数のPDFコンテンツを作成
    $pdfContents = [
        <<<'TEXT'
========================================
レジA 売上レポート
日付: 2024/01/15
========================================
商品名             数量  単価   金額
----------------------------------------
コーヒー            2    300    600
サンドイッチ        1    500    500
----------------------------------------
合計                            1100円
========================================
TEXT,
        <<<'TEXT'
========================================
レジA 売上レポート
日付: 2024/01/16
========================================
商品名             数量  単価   金額
----------------------------------------
コーヒー            3    300    900
紅茶                1    250    250
----------------------------------------
合計                            1150円
========================================
TEXT,
        <<<'TEXT'
========================================
レジA 売上レポート
日付: 2024/01/17
========================================
商品名             数量  単価   金額
----------------------------------------
サンドイッチ        2    500    1000
紅茶                2    250    500
----------------------------------------
合計                            1500円
========================================
TEXT,
    ];

    // テスト用のPDFファイルを作成
    $pdfPaths = [];
    foreach ($pdfContents as $index => $content) {
        $path = storage_path("app/pdf_temp/test_pdf_{$index}.txt");
        file_put_contents($path, $content);
        $pdfPaths[] = $path;
    }

    // PdfReaderをモック
    $mockPdfReader = mock(PdfReader::class);
    foreach ($pdfPaths as $index => $path) {
        $mockPdfReader->shouldReceive('extract')
            ->with($path)
            ->andReturn($pdfContents[$index]);
    }

    // サービスを実行
    $pdfImportService = new PdfImportService(
        $mockPdfReader,
        new ParserFactory(),
        new Normalizer(),
        new Aggregator(),
        new ExcelExporter()
    );

    $excelPath = $pdfImportService->import($pdfPaths);

    // 結果の検証
    expect($excelPath)->toBeString();
    expect(File::exists($excelPath))->toBeTrue();

    // クリーンアップ
    foreach ($pdfPaths as $path) {
        File::delete($path);
    }
    File::delete($excelPath);
})->group('e2e', 'multiple');
