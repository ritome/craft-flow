<?php

declare(strict_types=1);

use App\Services\Aggregator;
use App\Services\Normalizer;
use App\Services\Parsers\PosAParser;

test('PosAParser、Normalizer、Aggregatorの統合動作確認', function () {
    // サンプルPDFテキストデータ（レジA形式）
    $pdfTexts = [
        // ファイル1: 2024-01-15
        <<<'TEXT'
========================================
レジA 売上レポート
日付: 2024/01/15
========================================
商品名             数量  単価   金額
----------------------------------------
コーヒー            2    300    600
サンドイッチ        1    500    500
紅茶                3    350    1050
----------------------------------------
合計                            2150円
========================================
TEXT,
        // ファイル2: 2024-01-15（同日の別ファイル）
        <<<'TEXT'
========================================
レジA 売上レポート
日付: 2024/01/15
========================================
商品名             数量  単価   金額
----------------------------------------
コーヒー            5    300    1500
ケーキ              2    450    900
----------------------------------------
合計                            2400円
========================================
TEXT,
        // ファイル3: 2024-01-16
        <<<'TEXT'
========================================
レジA 売上レポート
日付: 2024/01/16
========================================
商品名             数量  単価   金額
----------------------------------------
コーヒー            3    300    900
紅茶                2    350    700
サンドイッチ        1    500    500
----------------------------------------
合計                            2100円
========================================
TEXT,
    ];

    // 各サービスのインスタンス化
    $parser = new PosAParser();
    $normalizer = new Normalizer();
    $aggregator = new Aggregator();

    // ステップ1: パース
    $parsedDataList = [];
    foreach ($pdfTexts as $index => $text) {
        expect($parser->canParse($text))->toBeTrue();
        $parsedData = $parser->parse($text);
        $parsedData['source'] = "file{$index}.pdf";
        $parsedData['parser'] = 'PosAParser';
        $parsedDataList[] = $parsedData;
    }

    expect($parsedDataList)->toHaveCount(3);

    // ステップ2: 正規化
    $normalizedDataList = [];
    foreach ($parsedDataList as $parsedData) {
        $normalizedDataList[] = $normalizer->normalize($parsedData);
    }

    expect($normalizedDataList)->toHaveCount(3);
    expect($normalizedDataList[0])->toHaveKeys(['date', 'items', 'total', 'metadata']);

    // ステップ3: 集計
    $aggregated = $aggregator->aggregate($normalizedDataList);

    // 合計売上の検証
    expect($aggregated['total_sales'])->toBe(6650); // 2150 + 2400 + 2100

    // 商品別集計の検証
    expect($aggregated['items'])->toHaveCount(4); // コーヒー、サンドイッチ、紅茶、ケーキ

    // コーヒーの集計（全ファイルに含まれる）
    $coffeeItem = collect($aggregated['items'])->firstWhere('name', 'コーヒー');
    expect($coffeeItem)
        ->not->toBeNull()
        ->and($coffeeItem['total_qty'])->toBe(10) // 2 + 5 + 3
        ->and($coffeeItem['total_price'])->toBe(3000) // 600 + 1500 + 900
        ->and($coffeeItem['count'])->toBe(3); // 3ファイルに登場

    // サンドイッチの集計
    $sandwichItem = collect($aggregated['items'])->firstWhere('name', 'サンドイッチ');
    expect($sandwichItem)
        ->not->toBeNull()
        ->and($sandwichItem['total_qty'])->toBe(2) // 1 + 1
        ->and($sandwichItem['total_price'])->toBe(1000); // 500 + 500

    // 紅茶の集計
    $teaItem = collect($aggregated['items'])->firstWhere('name', '紅茶');
    expect($teaItem)
        ->not->toBeNull()
        ->and($teaItem['total_qty'])->toBe(5) // 3 + 2
        ->and($teaItem['total_price'])->toBe(1750); // 1050 + 700

    // ケーキの集計
    $cakeItem = collect($aggregated['items'])->firstWhere('name', 'ケーキ');
    expect($cakeItem)
        ->not->toBeNull()
        ->and($cakeItem['total_qty'])->toBe(2)
        ->and($cakeItem['total_price'])->toBe(900);

    // 日別売上の検証
    expect($aggregated['daily_sales'])->toHaveCount(2); // 2024-01-15 と 2024-01-16

    $day1 = collect($aggregated['daily_sales'])->firstWhere('date', '2024-01-15');
    expect($day1)
        ->not->toBeNull()
        ->and($day1['sales'])->toBe(4550) // 2150 + 2400
        ->and($day1['files_count'])->toBe(2);

    $day2 = collect($aggregated['daily_sales'])->firstWhere('date', '2024-01-16');
    expect($day2)
        ->not->toBeNull()
        ->and($day2['sales'])->toBe(2100)
        ->and($day2['files_count'])->toBe(1);

    // サマリーの検証
    expect($aggregated['summary'])
        ->toHaveKeys(['total_files', 'total_items_count', 'unique_items_count', 'date_range', 'sources'])
        ->and($aggregated['summary']['total_files'])->toBe(3)
        ->and($aggregated['summary']['unique_items_count'])->toBe(4)
        ->and($aggregated['summary']['date_range']['start'])->toBe('2024-01-15')
        ->and($aggregated['summary']['date_range']['end'])->toBe('2024-01-16')
        ->and($aggregated['summary']['sources'])->toHaveCount(3);
});

test('異なる形式のデータが混在してもNormalizerで統一される', function () {
    $normalizer = new Normalizer();
    $aggregator = new Aggregator();

    // 異なる形式のパースデータ
    $parsedDataList = [
        // 標準形式
        [
            'date' => '2024-01-15',
            'items' => [
                ['name' => 'コーヒー', 'qty' => 2, 'price' => 600],
            ],
            'total' => 600,
            'source' => 'standard.pdf',
        ],
        // 代替キー名を使用
        [
            'date' => '2024-01-15',
            'items' => [
                ['name' => 'コーヒー', 'quantity' => 3, 'amount' => 900],
            ],
            'source' => 'alternative.pdf',
        ],
        // 文字列数値
        [
            'date' => '2024-01-15',
            'items' => [
                ['name' => 'コーヒー', 'qty' => '1', 'price' => '300円'],
            ],
            'total' => '300',
            'source' => 'string.pdf',
        ],
    ];

    // 正規化
    $normalizedDataList = array_map(
        fn ($data) => $normalizer->normalize($data),
        $parsedDataList
    );

    // すべて統一形式になっていることを確認
    foreach ($normalizedDataList as $normalized) {
        expect($normalized)
            ->toHaveKeys(['date', 'items', 'total', 'metadata'])
            ->and($normalized['items'][0])->toHaveKeys(['name', 'qty', 'price', 'unit_price'])
            ->and($normalized['items'][0]['qty'])->toBeInt()
            ->and($normalized['items'][0]['price'])->toBeInt();
    }

    // 集計
    $aggregated = $aggregator->aggregate($normalizedDataList);

    // 正しく集計されていることを確認
    $coffeeItem = collect($aggregated['items'])->firstWhere('name', 'コーヒー');
    expect($coffeeItem)
        ->not->toBeNull()
        ->and($coffeeItem['total_qty'])->toBe(6) // 2 + 3 + 1
        ->and($coffeeItem['total_price'])->toBe(1800) // 600 + 900 + 300
        ->and($coffeeItem['count'])->toBe(3);
});
