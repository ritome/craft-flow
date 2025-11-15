<?php

declare(strict_types=1);

namespace App\Services;

/**
 * PDFからテキストを抽出するサービス
 */
class PdfReader
{
    /**
     * PDFファイルからテキストを抽出
     *
     * @param string $filePath PDFファイルパス
     * @return string 抽出されたテキスト
     */
    public function extractText(string $filePath): string
    {
        // TODO: 実装
        return '';
    }
}

