<?php

use function Livewire\Volt\{state, rules}; // ★ useにrulesを追加
use App\Models\Reservation;
use App\Models\ExperienceProgram;
use Illuminate\Support\Collection;

// --- フォームの状態管理 ---
state(['programs' => fn() => ExperienceProgram::all(['experience_program_id', 'name'])]);
state([
    'experience_program_id' => '',
    'reservation_date' => now()->format('Y-m-d'),
    'reservation_time' => '10:00',
    'customer_name' => '',
    'customer_phone' => '',
    'customer_email' => '',
    'participant_count' => 1,
    'reservation_source' => 'hp',
    'status' => 1,
    'notes' => '',
]);

// ★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★
// ★★★ このrules()の定義が抜けていたことがエラーの原因です ★★★
// ★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★
rules([
    'experience_program_id' => 'required|exists:experience_programs,experience_program_id',
    'reservation_date' => 'required|date',
    'reservation_time' => 'required|date_format:H:i',
    'customer_name' => 'required|string|max:255',
    'customer_phone' => 'nullable|string|max:20',
    'customer_email' => 'nullable|email|max:255',
    'participant_count' => 'required|integer|min:1',
    'reservation_source' => 'required|string|max:255',
    'status' => 'required|integer|in:1,2,3',
    'notes' => 'nullable|string',
]);

// --- 登録処理 ---
$store = function () {
    // バリデーションを実行
    $validatedData = $this->validate();

    // --- ダブルブッキングチェック ---
    $program = ExperienceProgram::find($validatedData['experience_program_id']);
    if ($program) {
        $capacity = $program->capacity;

        $currentParticipants = Reservation::where('experience_program_id', $validatedData['experience_program_id'])
            ->where('reservation_date', $validatedData['reservation_date'])
            ->where('reservation_time', $validatedData['reservation_time'] . ':00')
            ->where('status', 1) // 予約済みのものだけをカウント
            ->sum('participant_count');

        if ($currentParticipants + $validatedData['participant_count'] > $capacity) {
            session()->flash('error', "申し訳ありません。その日時は満員のため、予約できません。(現在の予約人数: {$currentParticipants}名 / 定員: {$capacity}名)");
            return;
        }
    }
    // --- チェックここまで ---

    // 時刻に秒を追加
    $validatedData['reservation_time'] .= ':00';

    // データベースに保存
    Reservation::create($validatedData);

    session()->flash('message', '新しい予約が正常に登録されました。');
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

        <!-- フォームのコンテナ -->
        <form wire:submit="store">
            <div class="bg-white rounded-lg shadow-xl overflow-hidden">
                <div class="p-6 sm:p-8">
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">予約の新規登録</h1>
                    <p class="text-sm text-gray-500 mb-6">必要な情報を入力し、登録ボタンを押してください。</p>

                    {{-- ★★★ エラーメッセージ表示ブロックを追加 ★★★ --}}
                    @if (session()->has('error'))
                        <div class="mb-6 p-4 text-sm text-red-800 bg-red-100 rounded-lg border border-red-300"
                            role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="space-y-6">

                        <!-- プログラム選択 -->
                        <div>
                            <label for="experience_program_id" class="block text-sm font-medium text-gray-700">プログラム
                                <span class="text-red-500">*</span></label>
                            <select id="experience_program_id" wire:model="experience_program_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">-- プログラムを選択してください --</option>
                                @foreach ($programs as $program)
                                    <option value="{{ $program->experience_program_id }}">
                                        {{ $program->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('experience_program_id')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 予約日 -->
                        <div>
                            <label for="reservation_date" class="block text-sm font-medium text-gray-700">予約日 <span
                                    class="text-red-500">*</span></label>
                            <input type="date" wire:model="reservation_date" id="reservation_date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('reservation_date')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 予約時刻 -->
                        <div>
                            <label for="reservation_time" class="block text-sm font-medium text-gray-700">予約時刻 <span
                                    class="text-red-500">*</span></label>
                            <input type="time" wire:model="reservation_time" id="reservation_time"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('reservation_time')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 予約者名 -->
                        <div>
                            <label for="customer_name" class="block text-sm font-medium text-gray-700">予約者名 <span
                                    class="text-red-500">*</span></label>
                            <input type="text" wire:model="customer_name" id="customer_name" placeholder="例: 山田 太郎"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('customer_name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 参加人数 -->
                        <div>
                            <label for="participant_count" class="block text-sm font-medium text-gray-700">参加人数 <span
                                    class="text-red-500">*</span></label>
                            <input type="number" wire:model="participant_count" id="participant_count" min="1"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('participant_count')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 予約経路 -->
                        <div>
                            <label for="reservation_source" class="block text-sm font-medium text-gray-700">予約経路 <span
                                    class="text-red-500">*</span></label>
                            <select id="reservation_source" wire:model="reservation_source"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="hp">自社HP</option>
                                <option value="jalan">じゃらん</option>
                                <option value="asoview">アソビュー</option>
                                <option value="self_call">自社の電話</option>
                                <option value="center_call">センターの電話</option>
                                <option value="other">その他</option>
                            </select>
                            @error('reservation_source')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 予約状態 -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">予約状態 <span
                                    class="text-red-500">*</span></label>
                            <select id="status" wire:model="status"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="1">1: 予約済</option>
                                <option value="2">2: キャンセル済</option>
                                <option value="3">3: 完了</option>
                            </select>
                            @error('status')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 電話番号 -->
                        <div>
                            <label for="customer_phone" class="block text-sm font-medium text-gray-700">電話番号</label>
                            <input type="tel" wire:model="customer_phone" id="customer_phone"
                                placeholder="09012345678"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('customer_phone')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- メールアドレス -->
                        <div>
                            <label for="customer_email" class="block text-sm font-medium text-gray-700">メールアドレス</label>
                            <input type="email" wire:model="customer_email" id="customer_email"
                                placeholder="example@email.com"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('customer_email')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 備考 -->
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700">備考</label>
                            <textarea wire:model="notes" id="notes" rows="4" placeholder="アレルギー情報や、特別なご要望などがあればご記入ください。"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                            @error('notes')
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
