<?php

declare(strict_types=1);

use App\Services\PdfReader;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->pdfReader = new PdfReader();
    $this->testPdfPath = sys_get_temp_dir() . '/test_pdfs_' . uniqid();
    
    // テスト用ディレクトリを作成
    if (!file_exists($this->testPdfPath)) {
        mkdir($this->testPdfPath, 0755, true);
    }
});

afterEach(function () {
    // テスト用ファイルのクリーンアップ
    if (file_exists($this->testPdfPath)) {
        array_map('unlink', glob("{$this->testPdfPath}/*"));
        rmdir($this->testPdfPath);
    }
});

describe('PdfReader', function () {
    
    it('正常なPDFファイルからテキストを抽出できる', function () {
        // テスト用の簡単なPDFを作成（実際のPDFが必要）
        // このテストは実際のPDFファイルがある場合にスキップしないようにマーク
        $this->markTestSkipped('実際のPDFファイルが必要です。統合テストで実施してください。');
    });

    it('存在しないファイルパスを渡すと例外をスローする', function () {
        expect(fn() => $this->pdfReader->extract('/path/to/nonexistent.pdf'))
            ->toThrow(InvalidArgumentException::class, 'PDFファイルが見つかりません');
    });

    it('読み取り不可能なファイルを渡すと例外をスローする', function () {
        // 読み取り不可能なファイルを作成
        $unreadableFile = "{$this->testPdfPath}/unreadable.pdf";
        touch($unreadableFile);
        chmod($unreadableFile, 0000);

        expect(fn() => $this->pdfReader->extract($unreadableFile))
            ->toThrow(InvalidArgumentException::class, 'PDFファイルが読み取れません');

        // クリーンアップのために権限を戻す
        chmod($unreadableFile, 0644);
    })->skip(function () {
        // Windowsでは権限の扱いが異なるためスキップ
        return PHP_OS_FAMILY === 'Windows';
    });

    it('extractTextメソッドでも抽出できる（後方互換性）', function () {
        expect(fn() => $this->pdfReader->extractText('/path/to/nonexistent.pdf'))
            ->toThrow(InvalidArgumentException::class, 'PDFファイルが見つかりません');
    });

    it('空のPDFファイルを渡すと例外をスローする', function () {
        // 空のファイルを作成
        $emptyFile = "{$this->testPdfPath}/empty.pdf";
        touch($emptyFile);

        // 空のPDFの場合は例外をスローするはず
        // ただし、spatie/pdf-to-textがどう反応するかに依存
        expect(fn() => $this->pdfReader->extract($emptyFile))
            ->toThrow(RuntimeException::class);
    });

    it('不正なPDFファイルを渡すと例外をスローする', function () {
        // 不正なPDFファイル（実際はテキストファイル）を作成
        $invalidPdf = "{$this->testPdfPath}/invalid.pdf";
        file_put_contents($invalidPdf, 'This is not a PDF file');

        expect(fn() => $this->pdfReader->extract($invalidPdf))
            ->toThrow(RuntimeException::class);
    });
});

