<?php

declare(strict_types=1);

use App\Services\PdfReader;

beforeEach(function () {
    $this->pdfReader = new PdfReader;
});

describe('PdfReader統合テスト', function () {

    it('実際のPDFファイルからテキストを抽出できる', function () {
        // テスト用のPDFファイルが必要
        // tests/Fixtures/sample.pdf などに配置することを想定
        $samplePdfPath = base_path('tests/Fixtures/sample.pdf');

        if (! file_exists($samplePdfPath)) {
            $this->markTestSkipped('サンプルPDFファイルが見つかりません: '.$samplePdfPath);
        }

        $text = $this->pdfReader->extract($samplePdfPath);

        expect($text)->toBeString()
            ->and(strlen($text))->toBeGreaterThan(0);
    });

    it('日本語を含むPDFファイルからテキストを抽出できる', function () {
        $japanesePdfPath = base_path('tests/Fixtures/japanese_sample.pdf');

        if (! file_exists($japanesePdfPath)) {
            $this->markTestSkipped('日本語サンプルPDFファイルが見つかりません: '.$japanesePdfPath);
        }

        $text = $this->pdfReader->extract($japanesePdfPath);

        expect($text)->toBeString()
            ->and(strlen($text))->toBeGreaterThan(0)
            // 日本語が含まれていることを確認
            ->and(preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FFF}]/u', $text))->toBe(1);
    });

    it('複数ページのPDFファイルからテキストを抽出できる', function () {
        $multiPagePdfPath = base_path('tests/Fixtures/multipage_sample.pdf');

        if (! file_exists($multiPagePdfPath)) {
            $this->markTestSkipped('複数ページPDFファイルが見つかりません: '.$multiPagePdfPath);
        }

        $text = $this->pdfReader->extract($multiPagePdfPath);

        expect($text)->toBeString()
            ->and(strlen($text))->toBeGreaterThan(0);
    });

    it('レジPDFのサンプルからテキストを抽出できる', function () {
        // 実際のレジPDFのサンプル
        $registerPdfPath = base_path('tests/Fixtures/register_sample.pdf');

        if (! file_exists($registerPdfPath)) {
            $this->markTestSkipped('レジPDFサンプルが見つかりません: '.$registerPdfPath);
        }

        $text = $this->pdfReader->extract($registerPdfPath);

        expect($text)->toBeString()
            ->and(strlen($text))->toBeGreaterThan(0)
            // レジPDFには通常、日付や金額などが含まれる
            ->and(preg_match('/\d{4}[-\/]\d{2}[-\/]\d{2}/', $text))->toBeGreaterThan(0, '日付フォーマットが含まれていること');
    });

    it('パフォーマンス: 大きなPDFファイルの処理時間が妥当である', function () {
        $largePdfPath = base_path('tests/Fixtures/large_sample.pdf');

        if (! file_exists($largePdfPath)) {
            $this->markTestSkipped('大きなPDFファイルが見つかりません: '.$largePdfPath);
        }

        $startTime = microtime(true);
        $text = $this->pdfReader->extract($largePdfPath);
        $endTime = microtime(true);

        $executionTime = $endTime - $startTime;

        expect($text)->toBeString()
            ->and($executionTime)->toBeLessThan(10, 'PDF抽出は10秒以内に完了すること');
    });
});

describe('PdfReaderエラーハンドリング統合テスト', function () {

    it('破損したPDFファイルを適切にハンドリングする', function () {
        $corruptedPdfPath = base_path('tests/Fixtures/corrupted.pdf');

        if (! file_exists($corruptedPdfPath)) {
            // テスト用に破損したPDFを作成
            $testPath = storage_path('app/test_corrupted.pdf');
            file_put_contents($testPath, 'This is not a valid PDF content');
            $corruptedPdfPath = $testPath;
        }

        expect(fn () => $this->pdfReader->extract($corruptedPdfPath))
            ->toThrow(RuntimeException::class);

        // クリーンアップ
        if (file_exists(storage_path('app/test_corrupted.pdf'))) {
            unlink(storage_path('app/test_corrupted.pdf'));
        }
    });

    it('パスワード保護されたPDFを適切にハンドリングする', function () {
        $protectedPdfPath = base_path('tests/Fixtures/password_protected.pdf');

        if (! file_exists($protectedPdfPath)) {
            $this->markTestSkipped('パスワード保護PDFが見つかりません: '.$protectedPdfPath);
        }

        expect(fn () => $this->pdfReader->extract($protectedPdfPath))
            ->toThrow(RuntimeException::class);
    });
});
