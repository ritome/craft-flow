<?php

declare(strict_types=1);

use App\Services\ParserFactory;
use App\Services\Parsers\PosAParser;
use App\Services\Parsers\PosBParser;

/**
 * ParserFactoryのテスト
 */
describe('ParserFactory', function () {
    beforeEach(function () {
        $this->factory = new ParserFactory;
    });

    it('returns PosAParser for レジA format', function () {
        $text = <<<'TEXT'
========================================
レジA 売上レポート
日付: 2024/01/15
========================================
TEXT;

        $parser = $this->factory->getParser($text);

        expect($parser)->toBeInstanceOf(PosAParser::class);
    });

    it('returns PosBParser for POS-B format', function () {
        $text = <<<'TEXT'
========================================
*** POS-B システム ***
営業日: 2024-01-15
========================================
TEXT;

        $parser = $this->factory->getParser($text);

        expect($parser)->toBeInstanceOf(PosBParser::class);
    });

    it('throws exception for unknown format', function () {
        $text = <<<'TEXT'
Unknown POS System
Date: 2024-01-15
Item: Coffee
TEXT;

        expect(fn () => $this->factory->getParser($text))
            ->toThrow(
                InvalidArgumentException::class,
                '対応するパーサーが見つかりませんでした'
            );
    });

    it('returns available parsers list', function () {
        $parsers = ParserFactory::getAvailableParsers();

        expect($parsers)->toBeArray()
            ->and($parsers)->toContain(PosAParser::class)
            ->and($parsers)->toContain(PosBParser::class)
            ->and($parsers)->toHaveCount(2);
    });

    it('prioritizes first matching parser', function () {
        // レジAフォーマットを確実に判定できるテキスト
        $text = <<<'TEXT'
レジA 売上レポート
日付: 2024/01/15
商品名             数量  単価   金額
コーヒー            1    300    300
合計                            300円
TEXT;

        $parser = $this->factory->getParser($text);

        expect($parser)->toBeInstanceOf(PosAParser::class);
    });

    it('can parse complete レジA example through factory', function () {
        $text = <<<'TEXT'
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

        $parser = $this->factory->getParser($text);
        $result = $parser->parse($text);

        expect($result)->toHaveKey('date')
            ->and($result)->toHaveKey('items')
            ->and($result)->toHaveKey('total')
            ->and($result['date'])->toBe('2024-01-15')
            ->and($result['total'])->toBe(1100);
    });

    it('can parse complete POS-B example through factory', function () {
        $text = <<<'TEXT'
========================================
*** POS-B システム ***
営業日: 2024-01-15
========================================
[1] カフェラテ x 3 = ¥450
[2] クロワッサン x 1 = ¥280
========================================
総計: ¥730
========================================
TEXT;

        $parser = $this->factory->getParser($text);
        $result = $parser->parse($text);

        expect($result)->toHaveKey('date')
            ->and($result)->toHaveKey('items')
            ->and($result)->toHaveKey('total')
            ->and($result['date'])->toBe('2024-01-15')
            ->and($result['total'])->toBe(730);
    });
});
