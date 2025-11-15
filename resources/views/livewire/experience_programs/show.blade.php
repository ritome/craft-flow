<?php

use function Livewire\Volt\{state, on, dispatch};
use App\Models\ExperienceProgram;

// ルートモデルバインディング
state(['experience_programs' => fn(ExperienceProgram $experience_programs) => $experience_programs]);

// 編集ページにリダイレクト
$edit = fn() => redirect()->route('experience_programs.edit', $this->experience_programs);

// 削除処理
$destroy = function () {
    // 削除を実行
    $this->experience_programs->delete();

    // 一覧ページにイベントを発行して、削除成功メッセージを表示させる
    dispatch('program-deleted');
};

?>

<div class="bg-gray-100 p-4 sm:p-8 min-h-screen">
    <div class="max-w-3xl mx-auto">

        <!-- 戻るボタン -->
        <a href="{{ route('experience_programs.index') }}"
            class="mb-6 inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
            &larr; プログラム一覧に戻る
        </a>

        <!-- 詳細表示カード -->
        <div class="bg-white rounded-lg shadow-xl overflow-hidden">
            <!-- ヘッダー部分 -->
            <div class="p-6 sm:p-8 border-b border-gray-200">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">{{ $experience_programs->name }}</h1>
                <p class="mt-1 text-sm text-gray-500">プログラムID: {{ $experience_programs->experience_program_id }}</p>
            </div>

            <!-- 詳細リスト -->
            <dl class="divide-y divide-gray-200">
                <div class="py-4 px-6 sm:px-8 grid grid-cols-1 md:grid-cols-3 gap-2 md:gap-4">
                    <dt class="text-sm font-medium text-gray-500">所要時間</dt>
                    <dd class="text-sm text-gray-900 md:col-span-2">{{ $experience_programs->duration }} 分</dd>
                </div>

                <div class="py-4 px-6 sm:px-8 grid grid-cols-1 md:grid-cols-3 gap-2 md:gap-4">
                    <dt class="text-sm font-medium text-gray-500">料金 (税込)</dt>
                    <dd class="text-sm text-gray-900 md:col-span-2">&yen;
                        {{ number_format($experience_programs->price) }}</dd>
                </div>

                <div class="py-4 px-6 sm:px-8 grid grid-cols-1 md:grid-cols-3 gap-2 md:gap-4">
                    <dt class="text-sm font-medium text-gray-500">最大受入人数</dt>
                    <dd class="text-sm text-gray-900 md:col-span-2">{{ $experience_programs->capacity }} 名</dd>
                </div>

                <div class="py-4 px-6 sm:px-8 grid grid-cols-1 md:grid-cols-3 gap-2 md:gap-4">
                    <dt class="text-sm font-medium text-gray-500">プログラム説明</dt>
                    <dd class="text-sm text-gray-900 md:col-span-2 prose prose-sm max-w-none">
                        {!! nl2br(e($experience_programs->description)) !!}
                    </dd>
                </div>

                <div class="py-4 px-6 sm:px-8 grid grid-cols-1 md:grid-cols-3 gap-2 md:gap-4">
                    <dt class="text-sm font-medium text-gray-500">登録/更新日時</dt>
                    <dd class="text-sm text-gray-900 md:col-span-2">
                        登録: {{ $experience_programs->created_at?->format('Y/m/d H:i') }}<br>
                        更新: {{ $experience_programs->updated_at?->format('Y/m/d H:i') }}
                    </dd>
                </div>
            </dl>

            <!-- フッター/アクションボタン -->
            <div class="bg-gray-50 p-4 flex justify-end items-center space-x-4">
                <button wire:click="edit"
                    class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    編集する
                </button>
                <button wire:click="destroy" wire:confirm="このプログラムを本当に削除しますか？\n関連する予約情報に影響が出る可能性があります。"
                    class="inline-flex items-center justify-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    削除する
                </button>
            </div>
        </div>
    </div>
</div>
