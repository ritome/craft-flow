<?php

use function Livewire\Volt\{state, mount, rules};
use App\Models\ExperienceProgram;
use Illuminate\Validation\Rule;

// --- フォームの状態管理 ---

// 編集対象のプログラムインスタンス
state(['program' => null]);

// フォームの各入力値を保持する配列
state('form', [
    'name' => '',
    'description' => '',
    'duration' => '',
    'capacity' => '',
    'price' => '',
]);

// --- コンポーネント初期化処理 ---
mount(function (ExperienceProgram $experience_programs) {
    // ルートモデルバインディングで受け取ったインスタンスをstateにセット
    $this->program = $experience_programs;

    // フォームの初期値をモデルから設定
    $this->form = [
        'name' => $this->program->name,
        'description' => $this->program->description,
        'duration' => $this->program->duration,
        'capacity' => $this->program->capacity,
        'price' => $this->program->price,
    ];
});

// --- バリデーションルール ---
rules(
    fn() => [
        // 編集中のプログラム自身の名前はユニークチェックから除外する
        'form.name' => ['required', 'string', 'max:30', Rule::unique('experience_programs', 'name')->ignore($this->program->experience_program_id, 'experience_program_id')],
        'form.description' => 'nullable|string|max:2000',
        'form.duration' => 'required|integer|min:1',
        'form.capacity' => 'required|integer|min:1',
        'form.price' => 'required|integer|min:0',
    ],
);

// --- 更新処理 ---
$update = function () {
    // バリデーションを実行
    $validatedData = $this->validate();

    // データベースを更新
    $this->program->update($validatedData['form']);

    // フラッシュメッセージをセッションに保存
    session()->flash('message', 'プログラム情報が正常に更新されました。');

    // 詳細ページにリダイレクト
    return redirect()->route('experience_programs.show', $this->program);
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
        <form wire:submit="update">
            <div class="bg-white rounded-lg shadow-xl overflow-hidden">
                <div class="p-6 sm:p-8">
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">プログラム情報の更新</h1>
                    <p class="text-sm text-gray-500 mb-6">内容を編集して、更新ボタンを押してください。</p>

                    <div class="space-y-6">

                        <!-- プログラム名 -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">プログラム名 <span
                                    class="text-red-500">*</span></label>
                            <span class="text-xs text-gray-500">元の値: {{ $this->program->name }}</span>
                            <input type="text" wire:model="form.name" id="name"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('form.name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 所要時間 -->
                        <div>
                            <label for="duration" class="block text-sm font-medium text-gray-700">所要時間 (分) <span
                                    class="text-red-500">*</span></label>
                            <span class="text-xs text-gray-500">元の値: {{ $this->program->duration }} 分</span>
                            <input type="number" wire:model="form.duration" id="duration"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                min="1">
                            @error('form.duration')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 料金 -->
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700">料金 (円) <span
                                    class="text-red-500">*</span></label>
                            <span class="text-xs text-gray-500">元の値:
                                &yen;{{ number_format($this->program->price) }}</span>
                            <input type="number" wire:model="form.price" id="price"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                min="0">
                            @error('form.price')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 最大受入人数 -->
                        <div>
                            <label for="capacity" class="block text-sm font-medium text-gray-700">最大受入人数 <span
                                    class="text-red-500">*</span></label>
                            <span class="text-xs text-gray-500">元の値: {{ $this->program->capacity }} 名</span>
                            <input type="number" wire:model="form.capacity" id="capacity"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                min="1">
                            @error('form.capacity')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 説明 -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">説明</label>
                            <textarea wire:model="form.description" id="description" rows="6"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                            @error('form.description')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </div>

                <!-- フッター部分 -->
                <div class="bg-gray-50 px-6 py-4 flex justify-end">
                    <button type="submit"
                        class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        更新する
                    </button>
                </div>
            </div>
        </form>

    </div>
</div>
