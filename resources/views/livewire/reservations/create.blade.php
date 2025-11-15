<?php

use function Livewire\Volt\{state, rules};
use App\Models\Reservation;
use App\Models\ExperienceProgram;
use Illuminate\Validation\Rule;

state(['experience_program_id', 'reservation_date' => now()->format('Y-m-d'), 'reservation_time' => '10:00', 'customer_name', 'customer_phone', 'customer_email', 'participant_count' => 1, 'reservation_source', 'status', 'notes', 'programName' => null]);

// バリデーションルールを定義
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

// ★修正点: experience_program_id が変更されたときにプログラム名を取得するメソッドを追加
$updatedExperienceProgramId = function ($value) {
    // <-- public function を削除し、変数への代入に変更
    // IDが空でないか、有効な整数かを確認
    if (is_numeric($value) && $value > 0) {
        $program = ExperienceProgram::find($value);
        $this->programName = $program ? $program->name : 'プログラムIDが見つかりません';
    } else {
        $this->programName = null;
    }
};

// メモを保存する関数
$store = function () {
    $this->validate();
    // フォームからの入力値をデータベースへ保存
    Reservation::create($this->all());
    // 一覧ページにリダイレクト
    return redirect()->route('reservations.index');
};

?>

<main class="p-4 sm:p-8 bg-gray-50 shadow-sm sm:rounded-lg">
    <h1 class="text-3xl font-bold mb-6">予約 新規登録</h1>
    <div>
        <a href="{{ route('reservations.index') }}">戻る</a>
        <h1>新規登録</h1>
        <form wire:submit="store">
            <p>
                <label for="experience_program_id" class="block font-medium text-gray-700">プログラムID (必須)</label>
                <!-- ★修正点: wire:model.live を使用して入力のたびに Livewire に値を送信 -->
                <input type="number" wire:model.live="experience_program_id" id="experience_program_id" min="1"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                @error('experience_program_id')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror

                <!-- ★修正点: 動的にプログラム名を表示するブロックを追加 -->
                @if ($programName)
                    <span class="mt-2 text-sm font-semibold text-indigo-600">
                        プログラム名: {{ $programName }}
                    </span>
                @endif
            </p>
            <p>
                <label for="reservation_date">予約日</label>
                @error('reservation_date')
                    <span class="error">({{ $message }})</span>
                @enderror
                <br>
                <input type="text" wire:model="reservation_date" id="reservation_date">
            </p>

            <p>
                <label for="reservation_time">予約時刻</label>
                @error('reservation_time')
                    <span class="error">({{ $message }})</span>
                @enderror
                <br>

                <input type="text" wire:model="reservation_time" id="reservation_time">
            </p>

            <p>
                <label for="customer_name">予約者名</label>
                @error('customer_name')
                    <span class="error">({{ $message }})</span>
                @enderror
                <br>

                <input type="text" wire:model="customer_name" id="customer_name">
            </p>
            <p>
                <label for="customer_phone">電話番号</label>
                @error('customer_phone')
                    <span class="error">({{ $message }})</span>
                @enderror
                <br>

                <input type="text" wire:model="customer_phone" id="customer_phone">
            </p>
            <p>
                <label for="customer_email">メールアドレス</label>
                @error('customer_email')
                    <span class="error">({{ $message }})</span>
                @enderror
                <br>
                <input type="text" wire:model="customer_email" id="customer_email">
            </p>
            <p>
                <label for="participant_count">参加人数</label>
                @error('participant_count')
                    <span class="error">({{ $message }})</span>
                @enderror
                <br>

                <input type="text" wire:model="participant_count" id="participant_count">
            </p>

            <p>
                <label for="reservation_source">予約経路</label>
                @error('reservation_source')
                    <span class="error">({{ $message }})</span>
                @enderror
                <br>
                <input type="text" wire:model="reservation_source" id="reservation_source">
            </p>

            <p>
                <label for="status">予約状態</label>
                @error('status')
                    <span class="error">({{ $message }})</span>
                @enderror
                <br>
                <input type="text" wire:model="status" id="status">
            </p>

            <p>
                <label for="notes">備考</label>
                @error('notes')
                    <span class="error">({{ $message }})</span>
                @enderror
                <br>
                <textarea wire:model="notes" id="notes"></textarea>
            </p>

            <button type="submit">登録</button>
    </div>
    </form>

</main>
