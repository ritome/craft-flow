<?php

declare(strict_types=1);

use App\Services\Parsers\PosAParser;

/**
 * PosAParserのテスト
 */
describe('PosAParser', function () {
    beforeEach(function () {
        $this->parser = new PosAParser;
    });

    it('can parse valid レジA format', function () {
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

        $result = $this->parser->parse($text);

        expect($result)->toHaveKey('date')
            ->and($result)->toHaveKey('items')
            ->and($result)->toHaveKey('total')
            ->and($result['date'])->toBe('2024-01-15')
            ->and($result['total'])->toBe(1100)
            ->and($result['items'])->toHaveCount(2)
            ->and($result['items'][0])->toBe([
                'name' => 'コーヒー',
                'qty' => 2,
                'price' => 600,
            ])
            ->and($result['items'][1])->toBe([
                'name' => 'サンドイッチ',
                'qty' => 1,
                'price' => 500,
            ]);
    });

    it('can parse date with different separators', function () {
        $text = <<<'TEXT'
レジA 売上レポート
日付: 2024-03-25
商品名             数量  単価   金額
コーヒー            1    300    300
合計                            300円
TEXT;

        $result = $this->parser->parse($text);

        expect($result['date'])->toBe('2024-03-25');
    });

    it('can identify レジA format', function () {
        $text = <<<'TEXT'
レジA 売上レポート
日付: 2024/01/15
TEXT;

        expect($this->parser->canParse($text))->toBeTrue();
    });

    it('cannot identify non-レジA format', function () {
        $text = <<<'TEXT'
*** POS-B システム ***
営業日: 2024-01-15
TEXT;

        expect($this->parser->canParse($text))->toBeFalse();
    });

    it('throws exception when date is missing', function () {
        $text = <<<'TEXT'
レジA 売上レポート
商品名             数量  単価   金額
コーヒー            1    300    300
合計                            300円
TEXT;

        expect(fn () => $this->parser->parse($text))
            ->toThrow(InvalidArgumentException::class, '日付が見つかりませんでした');
    });

    it('throws exception when items are missing', function () {
        $text = <<<'TEXT'
レジA 売上レポート
日付: 2024/01/15
合計                            0円
TEXT;

        expect(fn () => $this->parser->parse($text))
            ->toThrow(InvalidArgumentException::class, '商品情報が見つかりませんでした');
    });

    it('throws exception when total is missing', function () {
        $text = <<<'TEXT'
レジA 売上レポート
日付: 2024/01/15
商品名             数量  単価   金額
コーヒー            1    300    300
TEXT;

        expect(fn () => $this->parser->parse($text))
            ->toThrow(InvalidArgumentException::class, '合計金額が見つかりませんでした');
    });

    it('throws exception when calculated total does not match', function () {
        $text = <<<'TEXT'
レジA 売上レポート
日付: 2024/01/15
商品名             数量  単価   金額
コーヒー            1    300    300
合計                            500円
TEXT;

        expect(fn () => $this->parser->parse($text))
            ->toThrow(InvalidArgumentException::class, '合計金額が一致しません');
    });

    it('can parse multiple items with various products', function () {
        $text = <<<'TEXT'
========================================
レジA 売上レポート
日付: 2024/05/10
========================================
商品名             数量  単価   金額
----------------------------------------
アイスコーヒー      3    350    1050
ホットサンド        2    450    900
ケーキセット        1    680    680
オレンジジュース    4    280    1120
----------------------------------------
合計                            3750円
========================================
TEXT;

        $result = $this->parser->parse($text);

        expect($result['items'])->toHaveCount(4)
            ->and($result['total'])->toBe(3750)
            ->and($result['date'])->toBe('2024-05-10');
    });
});
