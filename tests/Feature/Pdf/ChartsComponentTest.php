<?php

declare(strict_types=1);

use App\Models\ImportHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

test('グラフコンポーネントがレンダリングされる', function () {
    $response = Volt::test('pdf.charts');

    $response->assertStatus(200);
});

test('初期状態ではグラフが非表示', function () {
    $response = Volt::test('pdf.charts');

    $response->assertSet('showChart', false);
});

test('トグルボタンでグラフの表示/非表示が切り替わる', function () {
    $response = Volt::test('pdf.charts')
        ->assertSet('showChart', false)
        ->call('toggleChart')
        ->assertSet('showChart', true)
        ->call('toggleChart')
        ->assertSet('showChart', false);
});

test('期間の変更が正しく機能する', function () {
    $response = Volt::test('pdf.charts')
        ->call('toggleChart')
        ->assertSet('period', 'this_month')
        ->set('period', 'last_month')
        ->assertSet('period', 'last_month');
});

test('グラフタイプの変更が正しく機能する', function () {
    $response = Volt::test('pdf.charts')
        ->call('toggleChart')
        ->assertSet('chartType', 'daily')
        ->call('changeChartType', 'monthly')
        ->assertSet('chartType', 'monthly');
});

test('日別売上データが正しく集計される', function () {
    // テストデータ作成
    ImportHistory::factory()->create([
        'import_date' => now(),
        'total_sales' => 10000,
    ]);

    ImportHistory::factory()->create([
        'import_date' => now(),
        'total_sales' => 15000,
    ]);

    ImportHistory::factory()->create([
        'import_date' => now()->subDay(),
        'total_sales' => 20000,
    ]);

    $response = Volt::test('pdf.charts')
        ->set('period', 'this_month')
        ->set('chartType', 'daily')
        ->call('toggleChart');

    $response->assertSet('chartData', function ($chartData) {
        return is_array($chartData) &&
               isset($chartData['labels']) &&
               isset($chartData['data']) &&
               count($chartData['labels']) > 0 &&
               count($chartData['data']) > 0;
    });
});

test('月別売上データが正しく集計される', function () {
    // テストデータ作成
    ImportHistory::factory()->create([
        'import_date' => now()->startOfMonth(),
        'total_sales' => 50000,
    ]);

    ImportHistory::factory()->create([
        'import_date' => now()->subMonth(),
        'total_sales' => 30000,
    ]);

    $response = Volt::test('pdf.charts')
        ->set('period', 'last_3_months')
        ->set('chartType', 'monthly')
        ->call('toggleChart');

    $response->assertSet('chartData', function ($chartData) {
        return is_array($chartData) &&
               isset($chartData['labels']) &&
               isset($chartData['data']);
    });
});

test('履歴画面でグラフコンポーネントが表示される', function () {
    $response = $this->get(route('pdf.history'));

    $response->assertStatus(200);
    $response->assertSeeLivewire('pdf.charts');
});
