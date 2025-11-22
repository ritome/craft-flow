<?php

declare(strict_types=1);

use App\Services\Normalizer;

beforeEach(function () {
    $this->normalizer = new Normalizer;
});

test('正常なデータを正規化できる', function () {
    $parsedData = [
        'date' => '2024-01-15',
        'items' => [
            ['name' => 'コーヒー', 'qty' => 2, 'price' => 600],
            ['name' => 'サンドイッチ', 'qty' => 1, 'price' => 500],
        ],
        'total' => 1100,
    ];

    $result = $this->normalizer->normalize($parsedData);

    expect($result)
        ->toHaveKey('date')
        ->toHaveKey('items')
        ->toHaveKey('total')
        ->toHaveKey('metadata')
        ->and($result['date'])->toBe('2024-01-15')
        ->and($result['total'])->toBe(1100)
        ->and($result['items'])->toHaveCount(2)
        ->and($result['items'][0])->toMatchArray([
            'name' => 'コーヒー',
            'qty' => 2,
            'price' => 600,
        ]);
});

test('不足しているキーを補完する', function () {
    $parsedData = [
        'items' => [
            ['name' => 'コーヒー'], // qty と price がない
        ],
    ];

    $result = $this->normalizer->normalize($parsedData);

    expect($result['items'][0])
        ->toHaveKey('name')
        ->toHaveKey('qty')
        ->toHaveKey('price')
        ->toHaveKey('unit_price')
        ->and($result['items'][0]['qty'])->toBe(0)
        ->and($result['items'][0]['price'])->toBe(0);
});

test('文字列の数値を整数に変換する', function () {
    $parsedData = [
        'items' => [
            ['name' => 'コーヒー', 'qty' => '2', 'price' => '600'],
        ],
        'total' => '1100',
    ];

    $result = $this->normalizer->normalize($parsedData);

    expect($result['total'])->toBe(1100)
        ->and($result['items'][0]['qty'])->toBe(2)
        ->and($result['items'][0]['price'])->toBe(600);
});

test('カンマ区切りの数値を処理する', function () {
    $parsedData = [
        'items' => [
            ['name' => 'コーヒー', 'qty' => 2, 'price' => '1,000円'],
        ],
        'total' => '2,000円',
    ];

    $result = $this->normalizer->normalize($parsedData);

    expect($result['total'])->toBe(2000)
        ->and($result['items'][0]['price'])->toBe(1000);
});

test('totalが無い場合itemsから計算する', function () {
    $parsedData = [
        'items' => [
            ['name' => 'コーヒー', 'qty' => 2, 'price' => 600],
            ['name' => 'サンドイッチ', 'qty' => 1, 'price' => 500],
        ],
        // total キーがない
    ];

    $result = $this->normalizer->normalize($parsedData);

    expect($result['total'])->toBe(1100);
});

test('代替キー名（quantity, amount）を処理する', function () {
    $parsedData = [
        'items' => [
            ['name' => 'コーヒー', 'quantity' => 2, 'amount' => 600], // qty, price の代わり
        ],
    ];

    $result = $this->normalizer->normalize($parsedData);

    expect($result['items'][0]['qty'])->toBe(2)
        ->and($result['items'][0]['price'])->toBe(600);
});

test('空のデータでもエラーにならない', function () {
    $parsedData = [];

    $result = $this->normalizer->normalize($parsedData);

    expect($result)
        ->toHaveKey('date')
        ->toHaveKey('items')
        ->toHaveKey('total')
        ->toHaveKey('metadata')
        ->and($result['items'])->toBeArray()
        ->and($result['total'])->toBe(0);
});

test('メタデータが正しく設定される', function () {
    $parsedData = [
        'source' => 'test.pdf',
        'parser' => 'PosAParser',
        'items' => [],
    ];

    $result = $this->normalizer->normalize($parsedData);

    expect($result['metadata'])
        ->toHaveKey('source')
        ->toHaveKey('parser')
        ->toHaveKey('imported_at')
        ->and($result['metadata']['source'])->toBe('test.pdf')
        ->and($result['metadata']['parser'])->toBe('PosAParser');
});

test('日付形式を正規化する', function () {
    $testCases = [
        ['input' => '2024-01-15', 'expected' => '2024-01-15'],
        ['input' => '2024/01/15', 'expected' => '2024-01-15'],
        ['input' => '2024-1-5', 'expected' => '2024-01-05'],
    ];

    foreach ($testCases as $case) {
        $parsedData = ['date' => $case['input'], 'items' => []];
        $result = $this->normalizer->normalize($parsedData);
        expect($result['date'])->toBe($case['expected']);
    }
});
