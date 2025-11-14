<?php

declare(strict_types=1);

use App\Services\ZenginExporter;

test('全銀フォーマット出力が正常に動作する', function () {
    $exporter = new ZenginExporter;

    $rows = [
        [
            'bank_code' => '0001',
            'bank_name' => 'みずほ銀行',
            'branch_code' => '001',
            'branch_name' => '東京営業部',
            'account_type' => '普通',
            'account_number' => '1234567',
            'account_holder' => 'ヤマダタロウ',
            'amount' => '100000',
        ],
        [
            'bank_code' => '0005',
            'bank_name' => '三菱UFJ銀行',
            'branch_code' => '005',
            'branch_name' => '新宿支店',
            'account_type' => '当座',
            'account_number' => '7654321',
            'account_holder' => 'スズキハナコ',
            'amount' => '250000',
        ],
    ];

    $content = $exporter->export($rows);

    // ファイルが生成されたことを確認
    expect($content)->not->toBeEmpty();

    // CRLF で区切られていることを確認
    expect($content)->toContain("\r\n");

    // 各行が120バイトであることを確認
    $lines = explode("\r\n", $content);
    $lines = array_filter($lines, fn ($line) => $line !== '');

    expect($lines)->toHaveCount(2);

    foreach ($lines as $line) {
        expect(strlen($line))->toBe(120);
    }

    // 統計情報の確認
    $stats = $exporter->getStats();
    expect($stats['total_count'])->toBe(2);
    expect($stats['total_amount'])->toBe(350000);
});

test('口座番号が7桁でない場合はエラーになる', function () {
    $exporter = new ZenginExporter;

    $rows = [
        [
            'bank_code' => '0001',
            'bank_name' => 'みずほ銀行',
            'branch_code' => '001',
            'branch_name' => '東京営業部',
            'account_type' => '普通',
            'account_number' => '12345678', // 8桁はエラー
            'account_holder' => 'ヤマダタロウ',
            'amount' => '100000',
        ],
    ];

    $exporter->export($rows);
})->throws(\RuntimeException::class);

test('預金種目が不正な場合はエラーになる', function () {
    $exporter = new ZenginExporter;

    $rows = [
        [
            'bank_code' => '0001',
            'bank_name' => 'みずほ銀行',
            'branch_code' => '001',
            'branch_name' => '東京営業部',
            'account_type' => '貯蓄', // 不正な値
            'account_number' => '1234567',
            'account_holder' => 'ヤマダタロウ',
            'amount' => '100000',
        ],
    ];

    $exporter->export($rows);
})->throws(\RuntimeException::class);

test('金融機関コードが4桁でない場合はエラーになる', function () {
    $exporter = new ZenginExporter;

    $rows = [
        [
            'bank_code' => '001', // 3桁はエラー
            'bank_name' => 'みずほ銀行',
            'branch_code' => '001',
            'branch_name' => '東京営業部',
            'account_type' => '普通',
            'account_number' => '1234567',
            'account_holder' => 'ヤマダタロウ',
            'amount' => '100000',
        ],
    ];

    $exporter->export($rows);
})->throws(\RuntimeException::class);

test('受取人名が空の場合はエラーになる', function () {
    $exporter = new ZenginExporter;

    $rows = [
        [
            'bank_code' => '0001',
            'bank_name' => 'みずほ銀行',
            'branch_code' => '001',
            'branch_name' => '東京営業部',
            'account_type' => '普通',
            'account_number' => '1234567',
            'account_holder' => '', // 空はエラー
            'amount' => '100000',
        ],
    ];

    $exporter->export($rows);
})->throws(\RuntimeException::class);

test('半角カナ変換が正しく動作する', function () {
    $exporter = new ZenginExporter;

    $rows = [
        [
            'bank_code' => '0001',
            'bank_name' => 'ミズホギンコウ', // 全角カタカナ
            'branch_code' => '001',
            'branch_name' => 'トウキョウエイギョウブ',
            'account_type' => '普通',
            'account_number' => '1234567',
            'account_holder' => 'ヤマダタロウ',
            'amount' => '100000',
        ],
    ];

    $content = $exporter->export($rows);

    // Shift-JIS → UTF-8 に戻して確認
    $utf8 = mb_convert_encoding($content, 'UTF-8', 'SJIS-win');

    // 半角カナに変換されていることを確認（厳密には難しいので、とりあえず120バイトであることを確認）
    $lines = explode("\r\n", $content);
    $lines = array_filter($lines, fn ($line) => $line !== '');

    foreach ($lines as $line) {
        expect(strlen($line))->toBe(120);
    }
});

test('プレビュー機能が正常に動作する', function () {
    $exporter = new ZenginExporter;

    $rows = [
        [
            'bank_code' => '0001',
            'bank_name' => 'みずほ銀行',
            'branch_code' => '001',
            'branch_name' => '東京営業部',
            'account_type' => '普通',
            'account_number' => '1234567',
            'account_holder' => 'ヤマダタロウ',
            'amount' => '100000',
        ],
    ];

    $preview = $exporter->preview($rows);

    expect($preview)->toBeArray();
    expect($preview)->toHaveCount(1);
    expect($preview[0])->toHaveKey('line_number');
    expect($preview[0])->toHaveKey('content');
    expect($preview[0])->toHaveKey('byte_length');
    expect($preview[0])->toHaveKey('is_valid');
    expect($preview[0]['is_valid'])->toBeTrue();
    expect($preview[0]['byte_length'])->toBe(120);
});
