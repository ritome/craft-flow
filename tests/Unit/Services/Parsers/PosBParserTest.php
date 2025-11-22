<?php

declare(strict_types=1);

use App\Services\Parsers\PosBParser;

/**
 * PosBParserのテスト
 */
describe('PosBParser', function () {
    beforeEach(function () {
        $this->parser = new PosBParser;
    });

    it('can parse valid POS-B format', function () {
        $text = <<<'TEXT'
========================================
*** POS-B システム ***
営業日: 2024-01-15
========================================
[1] カフェラテ x 3 = ¥450
[2] クロワッサン x 1 = ¥280
[3] オレンジジュース x 2 = ¥400
========================================
総計: ¥1,130
========================================
TEXT;

        $result = $this->parser->parse($text);

        expect($result)->toHaveKey('date')
            ->and($result)->toHaveKey('items')
            ->and($result)->toHaveKey('total')
            ->and($result['date'])->toBe('2024-01-15')
            ->and($result['total'])->toBe(1130)
            ->and($result['items'])->toHaveCount(3)
            ->and($result['items'][0])->toBe([
                'name' => 'カフェラテ',
                'qty' => 3,
                'price' => 450,
            ])
            ->and($result['items'][1])->toBe([
                'name' => 'クロワッサン',
                'qty' => 1,
                'price' => 280,
            ])
            ->and($result['items'][2])->toBe([
                'name' => 'オレンジジュース',
                'qty' => 2,
                'price' => 400,
            ]);
    });

    it('can parse format without comma in total', function () {
        $text = <<<'TEXT'
*** POS-B システム ***
営業日: 2024-02-20
[1] コーヒー x 1 = ¥300
総計: ¥300
TEXT;

        $result = $this->parser->parse($text);

        expect($result['total'])->toBe(300)
            ->and($result['date'])->toBe('2024-02-20');
    });

    it('can parse format without yen symbol', function () {
        $text = <<<'TEXT'
*** POS-B システム ***
営業日: 2024-03-05
[1] サンドイッチ x 2 = 800
総計: 800
TEXT;

        $result = $this->parser->parse($text);

        expect($result['total'])->toBe(800)
            ->and($result['items'][0]['price'])->toBe(800);
    });

    it('can identify POS-B format', function () {
        $text = <<<'TEXT'
*** POS-B システム ***
営業日: 2024-01-15
TEXT;

        expect($this->parser->canParse($text))->toBeTrue();
    });

    it('cannot identify non-POS-B format', function () {
        $text = <<<'TEXT'
レジA 売上レポート
日付: 2024/01/15
TEXT;

        expect($this->parser->canParse($text))->toBeFalse();
    });

    it('throws exception when date is missing', function () {
        $text = <<<'TEXT'
*** POS-B システム ***
[1] コーヒー x 1 = ¥300
総計: ¥300
TEXT;

        expect(fn () => $this->parser->parse($text))
            ->toThrow(InvalidArgumentException::class, '日付が見つかりませんでした');
    });

    it('throws exception when items are missing', function () {
        $text = <<<'TEXT'
*** POS-B システム ***
営業日: 2024-01-15
総計: ¥0
TEXT;

        expect(fn () => $this->parser->parse($text))
            ->toThrow(InvalidArgumentException::class, '商品情報が見つかりませんでした');
    });

    it('throws exception when total is missing', function () {
        $text = <<<'TEXT'
*** POS-B システム ***
営業日: 2024-01-15
[1] コーヒー x 1 = ¥300
TEXT;

        expect(fn () => $this->parser->parse($text))
            ->toThrow(InvalidArgumentException::class, '合計金額が見つかりませんでした');
    });

    it('throws exception when calculated total does not match', function () {
        $text = <<<'TEXT'
*** POS-B システム ***
営業日: 2024-01-15
[1] コーヒー x 1 = ¥300
総計: ¥500
TEXT;

        expect(fn () => $this->parser->parse($text))
            ->toThrow(InvalidArgumentException::class, '合計金額が一致しません');
    });

    it('can parse date with slash separator', function () {
        $text = <<<'TEXT'
*** POS-B システム ***
営業日: 2024/06/18
[1] アイスティー x 1 = ¥350
総計: ¥350
TEXT;

        $result = $this->parser->parse($text);

        expect($result['date'])->toBe('2024-06-18');
    });

    it('can parse large amounts with commas', function () {
        $text = <<<'TEXT'
*** POS-B システム ***
営業日: 2024-07-25
[1] ディナーセット x 5 = ¥12,500
[2] ドリンクバー x 5 = ¥2,500
========================================
総計: ¥15,000
========================================
TEXT;

        $result = $this->parser->parse($text);

        expect($result['total'])->toBe(15000)
            ->and($result['items'][0]['price'])->toBe(12500)
            ->and($result['items'][1]['price'])->toBe(2500);
    });
});
