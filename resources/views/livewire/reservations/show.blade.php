<?php

use function Livewire\Volt\{state};
use App\Models\Reservation;
use App\Models\ExperienceProgram;

// ルートモデルバインディング
state(['reservations' => fn(Reservation $reservations) => $reservations]);

// 編集ページにリダイレクト
$edit = function () {
    // 編集ページにリダイレクト
    return redirect()->route('reservations.edit', $this->reservations);
};

$destroy = function () {
    $this->reservations->delete();
    return redirect()->route('reservations.index');
};

?>


<div class="bg-gray-100 p-4 sm:p-8 min-h-screen">
    <div class="max-w-3xl mx-auto">

        <!-- 戻るボタン -->
        <a href="{{ route('reservations.index') }}"
            class="mb-6 inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
            &larr; 予約カレンダーに戻る
        </a>

        <!-- 詳細表示カード -->
        <div class="bg-white rounded-lg shadow-xl overflow-hidden">
            <!-- ヘッダー部分 -->
            <div class="p-6 sm:p-8 border-b border-gray-200">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">予約詳細</h1>
                <p class="mt-1 text-sm text-gray-500">予約ID: {{ $reservations->id }}</p>
            </div>

            <!-- 詳細リスト -->
            <dl class="divide-y divide-gray-200">
                <div class="py-4 px-6 sm:px-8 grid grid-cols-1 md:grid-cols-3 gap-2 md:gap-4">
                    <dt class="text-sm font-medium text-gray-500">プログラム名</dt>
                    <dd class="text-sm text-gray-900 md:col-span-2 font-semibold">
                        {{ $reservations->experienceProgram?->name ?? 'プログラム情報なし' }}</dd>
                </div>

                <div class="py-4 px-6 sm:px-8 grid grid-cols-1 md:grid-cols-3 gap-2 md:gap-4">
                    <dt class="text-sm font-medium text-gray-500">予約者名</dt>
                    <dd class="text-sm text-gray-900 md:col-span-2">{{ $reservations->customer_name }}</dd>
                </div>

                <div class="py-4 px-6 sm:px-8 grid grid-cols-1 md:grid-cols-3 gap-2 md:gap-4">
                    <dt class="text-sm font-medium text-gray-500">予約日時</dt>
                    <dd class="text-sm text-gray-900 md:col-span-2">
                        {{ $reservations->reservation_date?->format('Y年n月j日') }}
                        {{ substr($reservations->reservation_time, 0, 5) }}</dd>
                </div>

                <div class="py-4 px-6 sm:px-8 grid grid-cols-1 md:grid-cols-3 gap-2 md:gap-4">
                    <dt class="text-sm font-medium text-gray-500">参加人数</dt>
                    <dd class="text-sm text-gray-900 md:col-span-2">{{ $reservations->participant_count }} 名</dd>
                </div>

                <div class="py-4 px-6 sm:px-8 grid grid-cols-1 md:grid-cols-3 gap-2 md:gap-4">
                    <dt class="text-sm font-medium text-gray-500">電話番号</dt>
                    <dd class="text-sm text-gray-900 md:col-span-2">{{ $reservations->customer_phone ?? '未登録' }}</dd>
                </div>

                <div class="py-4 px-6 sm:px-8 grid grid-cols-1 md:grid-cols-3 gap-2 md:gap-4">
                    <dt class="text-sm font-medium text-gray-500">メールアドレス</dt>
                    <dd class="text-sm text-gray-900 md:col-span-2">{{ $reservations->customer_email ?? '未登録' }}</dd>
                </div>

                <div class="py-4 px-6 sm:px-8 grid grid-cols-1 md:grid-cols-3 gap-2 md:gap-4">
                    <dt class="text-sm font-medium text-gray-500">予約経路</dt>
                    <dd class="text-sm text-gray-900 md:col-span-2">{{ $reservations->reservation_source }}</dd>
                </div>

                <div class="py-4 px-6 sm:px-8 grid grid-cols-1 md:grid-cols-3 gap-2 md:gap-4">
                    <dt class="text-sm font-medium text-gray-500">予約状態</dt>
                    <dd class="text-sm md:col-span-2">
                        @switch($reservations->status)
                            @case(1)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    予約済
                                </span>
                            @break

                            @case(2)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-200 text-gray-800">
                                    キャンセル済
                                </span>
                            @break

                            {{-- もし3の状態があるなら、ここに追加できます --}}
                            @case(3)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    完了
                                </span>
                            @break

                            @default
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    不明 ({{ $reservations->status }})
                                </span>
                        @endswitch
                    </dd>
                </div>

                <div class="py-4 px-6 sm:px-8 grid grid-cols-1 md:grid-cols-3 gap-2 md:gap-4">
                    <dt class="text-sm font-medium text-gray-500">備考</dt>
                    <dd class="text-sm text-gray-900 md:col-span-2 whitespace-pre-wrap">
                        {{ $reservations->notes ?? '特になし' }}</dd>
                </div>

                <div class="py-4 px-6 sm:px-8 grid grid-cols-1 md:grid-cols-3 gap-2 md:gap-4">
                    <dt class="text-sm font-medium text-gray-500">登録/更新日時</dt>
                    <dd class="text-sm text-gray-900 md:col-span-2">
                        登録: {{ $reservations->created_at?->format('Y/m/d H:i') }}<br>
                        更新: {{ $reservations->updated_at?->format('Y/m/d H:i') }}
                    </dd>
                </div>
            </dl>

            <!-- フッター/アクションボタン -->
            <div class="bg-gray-50 p-4 flex justify-end items-center space-x-4">
                <button wire:click="edit"
                    class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    編集する
                </button>
                <button wire:click="destroy" wire:confirm="この予約を本当に削除しますか？\nこの操作は取り消せません。"
                    class="inline-flex items-center justify-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    削除する
                </button>
            </div>
        </div>
    </div>
</div>
