<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Parsers\ParserInterface;

/**
 * 適切なパーサーを選択するファクトリクラス
 */
class ParserFactory
{
    /**
     * テキストに適したパーサーを取得
     *
     * @param string $text PDF抽出テキスト
     * @return ParserInterface 適切なパーサー
     * @throws \Exception 対応するパーサーが見つからない場合
     */
    public function getParser(string $text): ParserInterface
    {
        // TODO: 実装
        throw new \Exception('対応するパーサーが見つかりません');
    }
}

