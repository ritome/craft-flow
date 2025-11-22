<?php

use function Livewire\Volt\{state, rules};
use App\Models\ExperienceProgram;

// フォームの各入力値を保持
state([
    'name' => '',
    'description' => '',
    'duration' => 60,
    'capacity' => 10,
    'price' => 3000,
]);

// バリデーションルールを定義
rules([
    'name' => 'required|string|max:30|unique:experience_programs,name',
    'description' => 'nullable|string|max:2000',
    'duration' => 'required|integer|min:1',
    'capacity' => 'required|integer|min:1',
    'price' => 'required|integer|min:0',
]);

// 登録処理
$store = function () {
    // バリデーションを実行し、安全なデータのみを取得
    $validatedData = $this->validate();

    // データベースに保存
    ExperienceProgram::create($validatedData);

    // フラッシュメッセージをセッションに保存
    session()->flash('message', '新しいプログラムが正常に登録されました。');

    // 一覧ページにリダイレクト
    return redirect()->route('experience_programs.index');
};
?>

<div class="bg-gray-100 p-4 sm:p-8 min-h-screen">
    <div class="max-w-3xl mx-auto">

        <!-- 戻るボタン -->
        <a href="{{ route('experience_programs.index') }}"
            class="mb-6 inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
            &larr; プログラム一覧に戻る
        </a>

        <!-- フォームのコンテナ -->
        <form wire:submit="store">
            <div class="bg-white rounded-lg shadow-xl overflow-hidden">
                <div class="p-6 sm:p-8">
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">プログラムの新規登録</h1>
                    <p class="text-sm text-gray-500 mb-6">プログラムの情報を入力してください。</p>

                    <div class="space-y-6">

                        <!-- プログラム名 -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">プログラム名 <span
                                    class="text-red-500">*</span></label>
                            <input type="text" wire:model="name" id="name" placeholder="例: 藍染め体験"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 所要時間 -->
                        <div>
                            <label for="duration" class="block text-sm font-medium text-gray-700">所要時間 (分) <span
                                    class="text-red-500">*</span></label>
                            <input type="number" wire:model="duration" id="duration"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                min="1">
                            @error('duration')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 料金 -->
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700">料金 (円) <span
                                    class="text-red-500">*</span></label>
                            <input type="number" wire:model="price" id="price"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                min="0">
                            @error('price')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 最大受入人数 -->
                        <div>
                            <label for="capacity" class="block text-sm font-medium text-gray-700">最大受入人数 <span
                                    class="text-red-500">*</span></label>
                            <input type="number" wire:model="capacity" id="capacity"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                min="1">
                            @error('capacity')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 説明 -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">説明</label>
                            <textarea wire:model="description" id="description" rows="6" placeholder="プログラムの魅力や当日の流れなどを詳しく記入します。"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                            @error('description')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </div>

                <!-- フッター部分 -->
                <div class="bg-gray-50 px-6 py-4 flex justify-end">
                    <button type="submit"
                        class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        登録する
                    </button>
                </div>
            </div>
        </form>

    </div>
</div>
