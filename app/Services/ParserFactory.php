<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Parsers\ParserInterface;
use App\Services\Parsers\PosAParser;
use App\Services\Parsers\PosBParser;
use App\Services\Parsers\PosRegisterParser;
use InvalidArgumentException;

/**
 * 適切なパーサーを選択するファクトリクラス
 */
class ParserFactory
{
    /**
     * 利用可能なパーサークラスのリスト
     *
     * @var array<class-string<ParserInterface>>
     */
    private const AVAILABLE_PARSERS = [
        PosRegisterParser::class, // 岩手県センター POSレジ用（優先）
        PosAParser::class,        // テスト用
        PosBParser::class,        // テスト用
    ];

    /**
     * テキストに適したパーサーを取得
     *
     * @param  string  $text  PDF抽出テキスト
     * @return ParserInterface 適切なパーサー
     *
     * @throws InvalidArgumentException 対応するパーサーが見つからない場合
     */
    public function getParser(string $text): ParserInterface
    {
        foreach (self::AVAILABLE_PARSERS as $parserClass) {
            /** @var ParserInterface $parser */
            $parser = new $parserClass;

            if ($parser->canParse($text)) {
                return $parser;
            }
        }

        throw new InvalidArgumentException(
            '対応するパーサーが見つかりませんでした。PDFのフォーマットを確認してください。'
        );
    }

    /**
     * 利用可能なパーサークラスのリストを取得
     *
     * @return array<class-string<ParserInterface>>
     */
    public static function getAvailableParsers(): array
    {
        return self::AVAILABLE_PARSERS;
    }
}
