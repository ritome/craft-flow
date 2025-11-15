<?php

declare(strict_types=1);

namespace App\Services;

/**
 * PDFインポート処理を統括するサービス
 */
class PdfImportService
{
    public function __construct(
        private readonly PdfReader $pdfReader,
        private readonly ParserFactory $parserFactory,
        private readonly Normalizer $normalizer,
        private readonly Aggregator $aggregator,
        private readonly ExcelExporter $excelExporter,
    ) {
    }

    /**
     * PDFファイルをインポートしてExcelを出力
     *
     * @param array $pdfFiles アップロードされたPDFファイル配列
     * @return string 出力されたExcelファイルパス
     */
    public function import(array $pdfFiles): string
    {
        // TODO: 実装
        // 1. PDFからテキスト抽出
        // 2. テキストをパース
        // 3. データを正規化
        // 4. データを集計
        // 5. Excelとして出力
        return '';
    }
}

