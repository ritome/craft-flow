<?php

declare(strict_types=1);

namespace App\Services\Parsers;

/**
 * レジPDFパーサーのインターフェース
 */
interface ParserInterface
{
    /**
     * PDFテキストをパースして配列に変換
     *
     * @param  string  $text  PDF抽出テキスト
     * @return array パース結果
     */
    public function parse(string $text): array;

    /**
     * このパーサーが対応可能なフォーマットかチェック
     *
     * @param  string  $text  PDF抽出テキスト
     * @return bool 対応可能ならtrue
     */
    public function canParse(string $text): bool;
}
