<?php

use function Livewire\Volt\{state, rules};
use App\Models\Reservation;

state(['experience_program_id', 'reservation_date', 'reservation_time', 'customer_name', 'customer_phone', 'customer_email', 'participant_count', 'reservation_source', 'status', 'notes']);

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
// メモを保存する関数
$store = function () {
    $this->validate();
    // フォームからの入力値をデータベースへ保存
    Reservation::create($this->all());
    // 一覧ページにリダイレクト
    return redirect()->route('reservations.index');
};

?>

<div>
    <a href="{{ route('reservations.index') }}">戻る</a>
    <h1>新規登録</h1>
    <form wire:submit="store">
        <p>
            <label for="experience_program_id">プログラムID</label>
            @error('experience_program_id')
                <span class="error">({{ $message }})</span>
            @enderror
            <br>
            <input type="text" wire:model="experience_program_id" id="experience_program_id">
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
            <label for="status">ステータス</label>
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
    </form>
</div>
