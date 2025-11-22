<?php

use function Livewire\Volt\{state, mount, computed, updated};
use App\Models\ImportHistory;
use Illuminate\Support\Carbon;

// 状態管理
state([
    'chartType' => 'daily', // 'daily' or 'monthly'
    'period' => 'this_month', // プリセット期間
    'chartData' => null,
    'showChart' => false,
    'periods' => [
        'this_month' => '今月',
        'last_month' => '先月',
        'last_3_months' => '直近3ヶ月',
        'last_6_months' => '直近6ヶ月',
        'this_year' => '今年',
    ],
]);

// グラフを表示・非表示切り替え
$toggleChart = function () {
    $this->showChart = !$this->showChart;
    if ($this->showChart) {
        $this->loadChartData();
    }
};

// 期間が変更された時に自動的にデータを再読み込み
updated(['period' => fn() => $this->loadChartData()]);

// グラフタイプ変更時の処理
$changeChartType = function ($newType) {
    $this->chartType = $newType;
    $this->loadChartData();
};

// チャートデータを読み込む
$loadChartData = function () {
    $dateRange = $this->getDateRange($this->period);

    if ($this->chartType === 'daily') {
        $this->chartData = $this->getDailySalesData($dateRange['start'], $dateRange['end']);
    } else {
        $this->chartData = $this->getMonthlySalesData($dateRange['start'], $dateRange['end']);
    }
};

// 期間に基づいて開始日・終了日を取得
$getDateRange = function ($period) {
    $now = Carbon::now();

    return match ($period) {
        'this_month' => [
            'start' => $now->copy()->startOfMonth(),
            'end' => $now->copy()->endOfMonth(),
        ],
        'last_month' => [
            'start' => $now->copy()->subMonth()->startOfMonth(),
            'end' => $now->copy()->subMonth()->endOfMonth(),
        ],
        'last_3_months' => [
            'start' => $now->copy()->subMonths(3)->startOfMonth(),
            'end' => $now->copy()->endOfMonth(),
        ],
        'last_6_months' => [
            'start' => $now->copy()->subMonths(6)->startOfMonth(),
            'end' => $now->copy()->endOfMonth(),
        ],
        'this_year' => [
            'start' => $now->copy()->startOfYear(),
            'end' => $now->copy()->endOfYear(),
        ],
        default => [
            'start' => $now->copy()->startOfMonth(),
            'end' => $now->copy()->endOfMonth(),
        ],
    };
};

// 日別売上データを取得
$getDailySalesData = function ($startDate, $endDate) {
    $histories = ImportHistory::whereBetween('import_date', [$startDate, $endDate])
        ->orderBy('import_date')
        ->get();

    $dailyData = [];

    foreach ($histories as $history) {
        $date = $history->import_date->format('Y-m-d');

        if (!isset($dailyData[$date])) {
            $dailyData[$date] = 0;
        }

        $dailyData[$date] += (float) $history->total_sales;
    }

    // 日付の範囲を埋める（データがない日も0で表示）
    $labels = [];
    $data = [];
    $current = $startDate->copy();

    while ($current <= $endDate) {
        $dateKey = $current->format('Y-m-d');
        $labels[] = $current->format('m/d');
        $data[] = $dailyData[$dateKey] ?? 0;
        $current->addDay();
    }

    return [
        'labels' => $labels,
        'data' => $data,
    ];
};

// 月別売上データを取得
$getMonthlySalesData = function ($startDate, $endDate) {
    $histories = ImportHistory::whereBetween('import_date', [$startDate, $endDate])
        ->orderBy('import_date')
        ->get();

    $monthlyData = [];

    foreach ($histories as $history) {
        $month = $history->import_date->format('Y-m');

        if (!isset($monthlyData[$month])) {
            $monthlyData[$month] = 0;
        }

        $monthlyData[$month] += (float) $history->total_sales;
    }

    // 月の範囲を埋める
    $labels = [];
    $data = [];
    $current = $startDate->copy()->startOfMonth();

    while ($current <= $endDate) {
        $monthKey = $current->format('Y-m');
        $labels[] = $current->format('Y年m月');
        $data[] = $monthlyData[$monthKey] ?? 0;
        $current->addMonth();
    }

    return [
        'labels' => $labels,
        'data' => $data,
    ];
};

?>

<div>
    <!-- グラフ表示ボタン -->
    <div class="mb-6">
        <button wire:click="toggleChart"
            class="inline-flex items-center px-6 py-3 border border-blue-200 text-base font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors shadow-sm hover:shadow-md">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            @if ($showChart)
                📊 グラフを閉じる
            @else
                📊 売上グラフを表示
            @endif
        </button>
    </div>

    <!-- グラフ表示エリア -->
    @if ($showChart)
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8" x-data x-init="$watch('$wire.chartData', (value) => {
            if (value) {
                updateChart(value);
            }
        });
        
        function updateChart(chartData) {
            const ctx = document.getElementById('salesChart').getContext('2d');
        
            // 既存のチャートがあれば破棄
            if (window.salesChartInstance) {
                window.salesChartInstance.destroy();
            }
        
            // 新しいチャートを作成
            window.salesChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: '売上金額 (¥)',
                        data: chartData.data,
                        backgroundColor: 'rgba(79, 70, 229, 0.6)',
                        borderColor: 'rgba(79, 70, 229, 1)',
                        borderWidth: 2,
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '売上: ¥' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '¥' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }" wire:ignore>

            <!-- コントロールパネル -->
            <div class="mb-6 flex flex-wrap gap-4 items-center">
                <!-- グラフタイプ選択 -->
                <div class="flex gap-2">
                    <button wire:click="changeChartType('daily')"
                        class="px-4 py-2 rounded-md font-medium transition-colors {{ $chartType === 'daily' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                        日別
                    </button>
                    <button wire:click="changeChartType('monthly')"
                        class="px-4 py-2 rounded-md font-medium transition-colors {{ $chartType === 'monthly' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                        月別
                    </button>
                </div>

                <!-- 期間選択（ドロップダウン） -->
                <div class="flex items-center gap-2">
                    <label for="period-select" class="text-sm font-medium text-gray-700">表示期間:</label>
                    <select id="period-select" wire:model.live="period"
                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-700 font-medium transition-colors">
                        @foreach ($periods as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- グラフキャンバス -->
            <div class="relative" style="height: 400px;">
                <canvas id="salesChart"></canvas>
            </div>

            <!-- データサマリー -->
            @if ($chartData)
                <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <p class="text-sm text-blue-600 font-medium">合計売上</p>
                        <p class="text-2xl font-bold text-blue-900">
                            ¥{{ number_format(array_sum($chartData['data'])) }}
                        </p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <p class="text-sm text-green-600 font-medium">平均売上</p>
                        <p class="text-2xl font-bold text-green-900">
                            ¥{{ number_format(count($chartData['data']) > 0 ? array_sum($chartData['data']) / count($chartData['data']) : 0) }}
                        </p>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4">
                        <p class="text-sm text-purple-600 font-medium">データ件数</p>
                        <p class="text-2xl font-bold text-purple-900">
                            {{ count($chartData['labels']) }}件
                        </p>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
