<?php

declare(strict_types=1);

use App\Services\Parsers\PosRegisterParser;
use InvalidArgumentException;

describe('PosRegisterParser', function () {
    beforeEach(function () {
        $this->parser = new PosRegisterParser();
    });

    it('can parse returns true for valid format', function () {
        $text = <<<'TEXT'
レジ番号:POS1
営業日:令和7年11月5日
TEXT;

        expect($this->parser->canParse($text))->toBeTrue();
    });

    it('can parse returns false for invalid format', function () {
        $text = 'Invalid format without register number';

        expect($this->parser->canParse($text))->toBeFalse();
    });

    it('can parse returns false when missing register number', function () {
        $text = '営業日:令和7年11月5日';

        expect($this->parser->canParse($text))->toBeFalse();
    });

    it('can parse returns false when missing business date', function () {
        $text = 'レジ番号:POS1';

        expect($this->parser->canParse($text))->toBeFalse();
    });

    it('parses header information correctly', function () {
        $text = getSamplePdfText();

        $result = $this->parser->parse($text);

        expect($result['register_id'])->toBe('POS1');
        expect($result['business_date'])->toBe('2025-11-05');
        expect($result['output_datetime'])->toBe('2025-11-06 17:30:00');
    });

    it('parses items correctly', function () {
        $text = getSamplePdfText();

        $result = $this->parser->parse($text);

        expect($result['items'])->toBeArray();
        expect($result['items'])->not->toBeEmpty();

        // 最初の商品を検証（数量 > 0）
        $firstItem = $result['items'][0];
        expect($firstItem['product_code'])->toBe('P001');
        expect($firstItem['product_name'])->toBe('南部鉄器 急須(小)');
        expect($firstItem['unit_price'])->toBe(8500);
        expect($firstItem['quantity'])->toBe(1);
        expect($firstItem['subtotal'])->toBe(8500);
    });

    it('parses total correctly', function () {
        $text = getSamplePdfText();

        $result = $this->parser->parse($text);

        expect($result['total'])->toBe(89910);
    });

    it('includes only items with quantity greater than zero', function () {
        $text = getSamplePdfText();

        $result = $this->parser->parse($text);

        // 全ての商品の数量が0より大きいことを確認
        foreach ($result['items'] as $item) {
            expect($item['quantity'])->toBeGreaterThan(0);
        }
    });

    it('throws exception when register id not found', function () {
        $text = '営業日:令和7年11月5日';

        expect(fn () => $this->parser->parse($text))
            ->toThrow(InvalidArgumentException::class, 'レジ番号が見つかりませんでした');
    });

    it('throws exception when business date not found', function () {
        $text = 'レジ番号:POS1';

        expect(fn () => $this->parser->parse($text))
            ->toThrow(InvalidArgumentException::class, '営業日が見つかりませんでした');
    });

    it('throws exception when output datetime not found', function () {
        $text = <<<'TEXT'
レジ番号:POS1
営業日:令和7年11月5日
TEXT;

        expect(fn () => $this->parser->parse($text))
            ->toThrow(InvalidArgumentException::class, '出力日時が見つかりませんでした');
    });

    it('throws exception when no items found', function () {
        $text = <<<'TEXT'
レジ番号:POS1
営業日:令和7年11月5日
出力日時:令和7年11月6日 17時30分
合計 ¥0
TEXT;

        expect(fn () => $this->parser->parse($text))
            ->toThrow(InvalidArgumentException::class, '商品データが見つかりませんでした');
    });

    it('throws exception when total not found', function () {
        $text = <<<'TEXT'
レジ番号:POS1
営業日:令和7年11月5日
出力日時:令和7年11月6日 17時30分
P001 南部鉄器 急須(小) ¥8,500 1 ¥8,500 P016 わら細工 鍋敷き ¥800 0 ¥0
TEXT;

        expect(fn () => $this->parser->parse($text))
            ->toThrow(InvalidArgumentException::class, '合計金額が見つかりませんでした');
    });

    it('converts reiwa year to AD correctly', function () {
        $text = <<<'TEXT'
レジ番号:POS1
営業日:令和7年11月5日
出力日時:令和7年11月6日 17時30分
P001 テスト商品 ¥1,000 1 ¥1,000 P002 テスト商品2 ¥500 0 ¥0
合計 ¥1,000
TEXT;

        $result = $this->parser->parse($text);

        // 令和7年 = 2025年
        expect($result['business_date'])->toBe('2025-11-05');
        expect($result['output_datetime'])->toBe('2025-11-06 17:30:00');
    });

    it('parses prices with commas correctly', function () {
        $text = getSamplePdfText();

        $result = $this->parser->parse($text);

        // カンマ付き金額が正しく整数に変換されていることを確認
        expect($result['total'])->toBe(89910);

        foreach ($result['items'] as $item) {
            expect($item['unit_price'])->toBeInt();
            expect($item['subtotal'])->toBeInt();
        }
    });
});

/**
 * サンプルPDFテキストを取得
 */
function getSamplePdfText(): string
{
    return <<<'TEXT'
レジ番号:POS1
営業日:令和7年11月5日
出力日時:令和7年11月6日 17時30分

商品コード 商品名 単価 数量 小計 商品コード 商品名 単価 数量 小計
P001 南部鉄器 急須(小) ¥8,500 1 ¥8,500 P016 わら細工 鍋敷き ¥800 0 ¥0
P002 南部鉄器 風鈴 ¥3,200 3 ¥9,600 P017 わら細工 壁掛け飾り ¥2,000 2 ¥4,000
P003 南部鉄器 文鎮 ¥1,800 0 ¥0 P018 ホームスパン マフラー ¥15,000 1 ¥15,000
P004 南部せんべい(10枚入・ごま) ¥650 0 ¥0 P019 ホームスパン コースター(4枚組) ¥2,800 0 ¥0
P005 南部せんべい(10枚入・ピーナッツ) ¥650 1 ¥650 P020 盛岡冷麺(2食入・スープ付) ¥800 3 ¥2,400
P006 南部せんべい(10枚入・りんご) ¥750 2 ¥1,500 P021 盛岡冷麺(4食入・スープ付) ¥1,500 5 ¥7,500
P007 藍染ハンカチ ¥1,500 3 ¥4,500 P022 手作りクッキー詰合せ ¥1,200 2 ¥2,400
P008 藍染手ぬぐい ¥2,200 0 ¥0 P023 手作りジャム(りんご) ¥850 0 ¥0
P009 藍染トートバッグ ¥4,800 1 ¥4,800 P024 手作りジャム(ブルーベリー) ¥900 0 ¥0
P010 南部焼 湯呑み ¥2,800 1 ¥2,800 P025 木工品 お盆(中) ¥3,200 3 ¥9,600
P011 南部焼 飯椀 ¥2,500 2 ¥5,000 P026 木工品 箸置き(5個セット) ¥1,500 0 ¥0
P012 南部焼 マグカップ ¥3,500 0 ¥0 P027 螺鈿細工 ブローチ ¥6,500 1 ¥6,500
P013 竹細工 箸(5膳セット) ¥1,200 1 ¥1,200 P028 螺鈿細工 ペンダント ¥8,800 0 ¥0
P014 竹細工 弁当箱 ¥4,500 0 ¥0 P029 郷土玩具 チャグチャグ馬コ(小) ¥1,800 1 ¥1,800
P015 竹細工 茶托(5枚組) ¥3,800 0 ¥0 P030 岩手銘菓 かもめの玉子(8個入) ¥1,080 2 ¥2,160

合計 ¥89,910
TEXT;
}

