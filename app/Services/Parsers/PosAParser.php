<?php

declare(strict_types=1);

namespace App\Services\Parsers;

/**
 * レジA用のパーサー
 */
class PosAParser implements ParserInterface
{
    /**
     * PDFテキストをパースして配列に変換
     *
     * @param string $text PDF抽出テキスト
     * @return array パース結果
     */
    public function parse(string $text): array
    {
        // TODO: 実装
        return [];
    }

    /**
     * このパーサーが対応可能なフォーマットかチェック
     *
     * @param string $text PDF抽出テキスト
     * @return bool 対応可能ならtrue
     */
    public function canParse(string $text): bool
    {
        // TODO: 実装
        return false;
    }
}

