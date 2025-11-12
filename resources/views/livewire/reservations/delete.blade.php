<?php

use App\Models\Reservation;
use function Livewire\Volt\{state, mount};
use Illuminate\Support\Facades\Log;

state(['reservationId', 'reservation' => null, 'showDeleteModal' => false]);

mount(function ($reservationId) {
    // 削除対象の予約をロード
    $this->reservation = Reservation::findOrFail($reservationId);
});

// モーダル表示
$confirmDelete = function () {
    $this->showDeleteModal = true;
};

// 論理削除（ステータス変更）を実行
$delete = function () {
    if (!$this->reservation) {
        session()->flash('error', '予約が見つかりません。');
        $this->showDeleteModal = false;
        return;
    }

    // ステータスを「キャンセル (2)」に変更
    $this->reservation->update(['status' => 2]);
    $this->reservation->delete(); // 論理削除 (Soft Deletes) を実行

    session()->flash('success', '予約 #' . $this->reservation->id . ' は正常にキャンセルされました。');

    // 処理後、一覧画面にリダイレクト
    return $this->redirect('/reservations', navigate: true);
};

?>
<div>
    <h2 class="text-2xl font-bold mb-6 text-gray-800">予約のキャンセル/削除</h2>

    <div class="bg-white p-6 rounded-lg shadow-md max-w-lg mx-auto">
        @if (session()->has('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                {{ session('success') }}</div>
        @endif

        @if ($reservation)
            <p class="mb-4 text-center">以下の予約をキャンセル（論理削除）しますか？</p>

            <dl class="text-sm border border-gray-200 rounded-lg divide-y divide-gray-200">
                <div class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="font-medium text-gray-500">予約ID</dt>
                    <dd class="text-gray-900 sm:col-span-2">{{ $reservation->id }}</dd>
                </div>
                <div class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="font-medium text-gray-500">予約者名</dt>
                    <dd class="text-gray-900 sm:col-span-2">{{ $reservation->customer_name }}</dd>
                </div>
                <div class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="font-medium text-gray-500">日時</dt>
                    <dd class="text-gray-900 sm:col-span-2">{{ $reservation->reservation_date }}
                        {{ $reservation->reservation_time }}</dd>
                </div>
                <div class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="font-medium text-gray-500">人数</dt>
                    <dd class="text-gray-900 sm:col-span-2">{{ $reservation->participant_count }} 名</dd>
                </div>
            </dl>

            <div class="mt-8 flex justify-center">
                <button wire:click="confirmDelete" type="button"
                    class="px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    予約をキャンセルする
                </button>
            </div>
        @else
            <p class="text-center text-red-500">対象の予約が見つかりません。</p>
        @endif
    </div>

    <!-- 削除確認モーダル -->
    <div x-data="{ show: $wire.showDeleteModal }" x-show="show" x-on:keydown.escape.window="$wire.showDeleteModal = false"
        style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="show" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="show"
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">予約キャンセル確認</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500">本当にこの予約をキャンセルしてもよろしいですか？</p>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button wire:click="delete" type="button"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">
                        キャンセルを実行
                    </button>
                    <button wire:click="$set('showDeleteModal', false)" type="button"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                        戻る
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
