<?php

use function Livewire\Volt\{state, computed};
use Carbon\Carbon;
use App\Models\Reservation;

// 表示している月のCarbonインスタンスをstateとして保持
state(['currentDate' => fn() => Carbon::now()]);

// 表示月が変更されるたびに、その月の予約情報を再計算する算出プロパティ
$reservationsByDate = computed(function () {
    $startOfMonth = $this->currentDate->copy()->startOfMonth()->startOfDay();
    $endOfMonth = $this->currentDate->copy()->endOfMonth()->endOfDay();

    return Reservation::whereBetween('reservation_date', [$startOfMonth, $endOfMonth])
        ->orderBy('reservation_time') // 時間順にソート
        ->get()
        ->groupBy(fn($r) => Carbon::parse($r->reservation_date)->format('j'));
});

// カレンダーを構成するための情報を算出するプロパティ
$calendarInfo = computed(function () {
    $startOfMonth = $this->currentDate->copy()->startOfMonth();
    return [
        'daysInMonth' => $startOfMonth->daysInMonth,
        'startBlankDays' => $startOfMonth->dayOfWeek,
        'monthName' => $this->currentDate->format('Y年 n月'),
    ];
});

// アクション
$goToPreviousMonth = fn() => $this->currentDate->subMonth();
$goToNextMonth = fn() => $this->currentDate->addMonth();
$goToCurrentMonth = fn() => ($this->currentDate = Carbon::now());
$create = fn() => redirect()->route('reservations.create');

?>

<div class="p-4 bg-gray-50 min-h-screen">
    {{-- ヘッダー：年月表示とナビゲーション --}}
    <div class="flex items-center justify-between mb-6">

        <!-- 左側：タイトルとダッシュボードへのリンク -->
        <div>
            <h1 class="text-3xl font-bold text-gray-800">予約カレンダー</h1>
            <a href="{{ route('dashboard') }}" class="text-sm text-indigo-600 hover:text-indigo-800 hover:underline">
                &larr; ダッシュボードに戻る
            </a>
        </div>

        <!-- 右側：カレンダー操作と新規登録ボタン -->
        <div class="flex items-center space-x-4">
            <h2 class="text-2xl font-semibold text-gray-700 w-48 text-center">{{ $this->calendarInfo['monthName'] }}</h2>
            <div class="flex rounded-md shadow-sm">
                <button wire:click="goToPreviousMonth"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    &lt; 前の月
                </button>
                <button wire:click="goToCurrentMonth"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border-t border-b border-gray-300 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    今月
                </button>
                <button wire:click="goToNextMonth"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    次の月 &gt;
                </button>
            </div>
            <button wire:click="create"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                新規予約を登録
            </button>
        </div>
    </div>


    {{-- カレンダー本体 --}}
    <div wire:loading.class="opacity-50 transition-opacity"
        class="grid grid-cols-7 border-l border-t border-gray-200 shadow-lg">

        {{-- 曜日のヘッダー --}}
        @foreach (['日', '月', '火', '水', '木', '金', '土'] as $key => $day)
            <div @class([
                'py-3 text-center font-semibold text-sm text-gray-600 bg-gray-100 border-b border-r border-gray-200',
                'text-red-600' => $key === 0, // 日曜日
                'text-blue-600' => $key === 6, // 土曜日
            ])>
                {{ $day }}
            </div>
        @endforeach

        {{-- 月の初日までの空白マス --}}
        @foreach (range(1, $this->calendarInfo['startBlankDays']) as $_)
            <div class="bg-gray-50 border-b border-r border-gray-200"></div>
        @endforeach

        {{-- 日付マス --}}
        @foreach (range(1, $this->calendarInfo['daysInMonth']) as $day)
            @php
                $isToday = $day == now()->day && $currentDate->isCurrentMonth();
                $date = $currentDate->copy()->setDay($day);
                $isSunday = $date->isSunday();
                $isSaturday = $date->isSaturday();
            @endphp
            <div @class([
                'relative min-h-[140px] p-2 border-b border-r border-gray-200 flex flex-col',
                'bg-white' => !$isToday,
                'bg-indigo-50' => $isToday, // 今日の日付をハイライト
            ])>
                <div @class([
                    'text-sm font-semibold',
                    'text-red-600' => $isSunday,
                    'text-blue-600' => $isSaturday,
                    'text-gray-800' => !$isSunday && !$isSaturday,
                    'text-white bg-indigo-600 rounded-full w-6 h-6 flex items-center justify-center' => $isToday,
                ])>
                    {{ $day }}
                </div>

                {{-- その日の予約一覧 (スクロール可能にする) --}}
                <div class="mt-2 space-y-1 overflow-y-auto flex-grow">
                    @if (isset($this->reservationsByDate[$day]))
                        @foreach ($this->reservationsByDate[$day] as $reservation)
                            <a href="{{ route('reservations.show', $reservation) }}"
                                title="{{ $reservation->customer_name }}様 ({{ substr($reservation->reservation_time, 0, 5) }})"
                                class="block p-1.5 text-xs text-white bg-indigo-500 rounded-md hover:bg-indigo-600 transition-colors truncate">
                                {{ substr($reservation->reservation_time, 0, 5) }} {{ $reservation->customer_name }}
                            </a>
                        @endforeach
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
