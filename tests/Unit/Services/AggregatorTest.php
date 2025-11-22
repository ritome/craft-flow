<?php

declare(strict_types=1);

use App\Services\Aggregator;

beforeEach(function () {
    $this->aggregator = new Aggregator();
});

test('複数のデータを集計できる', function () {
    $normalizedDataList = [
        [
            'date' => '2024-01-15',
            'items' => [
                ['name' => 'コーヒー', 'qty' => 2, 'price' => 600, 'unit_price' => 300],
                ['name' => 'サンドイッチ', 'qty' => 1, 'price' => 500, 'unit_price' => 500],
            ],
            'total' => 1100,
            'metadata' => ['source' => 'file1.pdf', 'parser' => 'PosAParser', 'imported_at' => '2024-01-15 10:00:00'],
        ],
        [
            'date' => '2024-01-15',
            'items' => [
                ['name' => 'コーヒー', 'qty' => 3, 'price' => 900, 'unit_price' => 300],
                ['name' => '紅茶', 'qty' => 1, 'price' => 350, 'unit_price' => 350],
            ],
            'total' => 1250,
            'metadata' => ['source' => 'file2.pdf', 'parser' => 'PosAParser', 'imported_at' => '2024-01-15 11:00:00'],
        ],
    ];

    $result = $this->aggregator->aggregate($normalizedDataList);

    expect($result)
        ->toHaveKey('total_sales')
        ->toHaveKey('items')
        ->toHaveKey('daily_sales')
        ->toHaveKey('summary')
        ->and($result['total_sales'])->toBe(2350);
});

test('商品別の集計が正しく行われる', function () {
    $normalizedDataList = [
        [
            'date' => '2024-01-15',
            'items' => [
                ['name' => 'コーヒー', 'qty' => 2, 'price' => 600, 'unit_price' => 300],
                ['name' => 'サンドイッチ', 'qty' => 1, 'price' => 500, 'unit_price' => 500],
            ],
            'total' => 1100,
            'metadata' => ['source' => 'file1.pdf', 'parser' => 'PosAParser', 'imported_at' => '2024-01-15 10:00:00'],
        ],
        [
            'date' => '2024-01-15',
            'items' => [
                ['name' => 'コーヒー', 'qty' => 3, 'price' => 900, 'unit_price' => 300],
            ],
            'total' => 900,
            'metadata' => ['source' => 'file2.pdf', 'parser' => 'PosAParser', 'imported_at' => '2024-01-15 11:00:00'],
        ],
    ];

    $result = $this->aggregator->aggregate($normalizedDataList);

    // コーヒーの集計を確認
    $coffeeItem = collect($result['items'])->firstWhere('name', 'コーヒー');
    expect($coffeeItem)
        ->not->toBeNull()
        ->and($coffeeItem['total_qty'])->toBe(5)
        ->and($coffeeItem['total_price'])->toBe(1500)
        ->and($coffeeItem['count'])->toBe(2);

    // サンドイッチの集計を確認
    $sandwichItem = collect($result['items'])->firstWhere('name', 'サンドイッチ');
    expect($sandwichItem)
        ->not->toBeNull()
        ->and($sandwichItem['total_qty'])->toBe(1)
        ->and($sandwichItem['total_price'])->toBe(500)
        ->and($sandwichItem['count'])->toBe(1);
});

test('商品が売上金額の降順でソートされる', function () {
    $normalizedDataList = [
        [
            'date' => '2024-01-15',
            'items' => [
                ['name' => 'コーヒー', 'qty' => 1, 'price' => 300, 'unit_price' => 300],
                ['name' => 'サンドイッチ', 'qty' => 2, 'price' => 1000, 'unit_price' => 500],
                ['name' => '紅茶', 'qty' => 1, 'price' => 350, 'unit_price' => 350],
            ],
            'total' => 1650,
            'metadata' => ['source' => 'file1.pdf', 'parser' => 'PosAParser', 'imported_at' => '2024-01-15 10:00:00'],
        ],
    ];

    $result = $this->aggregator->aggregate($normalizedDataList);

    expect($result['items'][0]['name'])->toBe('サンドイッチ') // 1000円
        ->and($result['items'][1]['name'])->toBe('紅茶') // 350円
        ->and($result['items'][2]['name'])->toBe('コーヒー'); // 300円
});

test('日別売上が正しく集計される', function () {
    $normalizedDataList = [
        [
            'date' => '2024-01-15',
            'items' => [
                ['name' => 'コーヒー', 'qty' => 2, 'price' => 600, 'unit_price' => 300],
            ],
            'total' => 600,
            'metadata' => ['source' => 'file1.pdf', 'parser' => 'PosAParser', 'imported_at' => '2024-01-15 10:00:00'],
        ],
        [
            'date' => '2024-01-15',
            'items' => [
                ['name' => '紅茶', 'qty' => 1, 'price' => 350, 'unit_price' => 350],
            ],
            'total' => 350,
            'metadata' => ['source' => 'file2.pdf', 'parser' => 'PosAParser', 'imported_at' => '2024-01-15 11:00:00'],
        ],
        [
            'date' => '2024-01-16',
            'items' => [
                ['name' => 'コーヒー', 'qty' => 1, 'price' => 300, 'unit_price' => 300],
            ],
            'total' => 300,
            'metadata' => ['source' => 'file3.pdf', 'parser' => 'PosAParser', 'imported_at' => '2024-01-16 10:00:00'],
        ],
    ];

    $result = $this->aggregator->aggregate($normalizedDataList);

    expect($result['daily_sales'])->toHaveCount(2);

    // 2024-01-15
    $day1 = collect($result['daily_sales'])->firstWhere('date', '2024-01-15');
    expect($day1)
        ->not->toBeNull()
        ->and($day1['sales'])->toBe(950)
        ->and($day1['items_count'])->toBe(2)
        ->and($day1['files_count'])->toBe(2);

    // 2024-01-16
    $day2 = collect($result['daily_sales'])->firstWhere('date', '2024-01-16');
    expect($day2)
        ->not->toBeNull()
        ->and($day2['sales'])->toBe(300)
        ->and($day2['items_count'])->toBe(1)
        ->and($day2['files_count'])->toBe(1);
});

test('日別売上が日付の昇順でソートされる', function () {
    $normalizedDataList = [
        [
            'date' => '2024-01-17',
            'items' => [['name' => 'コーヒー', 'qty' => 1, 'price' => 300, 'unit_price' => 300]],
            'total' => 300,
            'metadata' => ['source' => 'file3.pdf', 'parser' => 'PosAParser', 'imported_at' => '2024-01-17 10:00:00'],
        ],
        [
            'date' => '2024-01-15',
            'items' => [['name' => 'コーヒー', 'qty' => 1, 'price' => 300, 'unit_price' => 300]],
            'total' => 300,
            'metadata' => ['source' => 'file1.pdf', 'parser' => 'PosAParser', 'imported_at' => '2024-01-15 10:00:00'],
        ],
        [
            'date' => '2024-01-16',
            'items' => [['name' => 'コーヒー', 'qty' => 1, 'price' => 300, 'unit_price' => 300]],
            'total' => 300,
            'metadata' => ['source' => 'file2.pdf', 'parser' => 'PosAParser', 'imported_at' => '2024-01-16 10:00:00'],
        ],
    ];

    $result = $this->aggregator->aggregate($normalizedDataList);

    expect($result['daily_sales'][0]['date'])->toBe('2024-01-15')
        ->and($result['daily_sales'][1]['date'])->toBe('2024-01-16')
        ->and($result['daily_sales'][2]['date'])->toBe('2024-01-17');
});

test('サマリー情報が正しく設定される', function () {
    $normalizedDataList = [
        [
            'date' => '2024-01-15',
            'items' => [
                ['name' => 'コーヒー', 'qty' => 2, 'price' => 600, 'unit_price' => 300],
                ['name' => 'サンドイッチ', 'qty' => 1, 'price' => 500, 'unit_price' => 500],
            ],
            'total' => 1100,
            'metadata' => ['source' => 'file1.pdf', 'parser' => 'PosAParser', 'imported_at' => '2024-01-15 10:00:00'],
        ],
        [
            'date' => '2024-01-16',
            'items' => [
                ['name' => '紅茶', 'qty' => 3, 'price' => 1050, 'unit_price' => 350],
            ],
            'total' => 1050,
            'metadata' => ['source' => 'file2.pdf', 'parser' => 'PosAParser', 'imported_at' => '2024-01-16 10:00:00'],
        ],
    ];

    $result = $this->aggregator->aggregate($normalizedDataList);

    expect($result['summary'])
        ->toHaveKey('total_files')
        ->toHaveKey('total_items_count')
        ->toHaveKey('unique_items_count')
        ->toHaveKey('date_range')
        ->toHaveKey('sources')
        ->and($result['summary']['total_files'])->toBe(2)
        ->and($result['summary']['total_items_count'])->toBe(6) // 2 + 1 + 3
        ->and($result['summary']['unique_items_count'])->toBe(3) // コーヒー、サンドイッチ、紅茶
        ->and($result['summary']['date_range']['start'])->toBe('2024-01-15')
        ->and($result['summary']['date_range']['end'])->toBe('2024-01-16')
        ->and($result['summary']['sources']['file1.pdf'])->toBe(1)
        ->and($result['summary']['sources']['file2.pdf'])->toBe(1);
});

test('空のデータリストでエラーにならない', function () {
    $result = $this->aggregator->aggregate([]);

    expect($result)
        ->toHaveKey('total_sales')
        ->toHaveKey('items')
        ->toHaveKey('daily_sales')
        ->toHaveKey('summary')
        ->and($result['total_sales'])->toBe(0)
        ->and($result['items'])->toBeEmpty()
        ->and($result['daily_sales'])->toBeEmpty()
        ->and($result['summary']['total_files'])->toBe(0);
});
