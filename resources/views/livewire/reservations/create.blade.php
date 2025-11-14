<?php

use function Livewire\Volt\{state};
use App\Models\Reservation;

state(['experience_program_id', 'reservation_date', 'reservation_time', 'customer_name', 'customer_phone', 'customer_email', 'participant_count', 'reservation_source', 'status', 'notes']);

// メモを保存する関数
$store = function () {
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
            <label for="experience_program_id">プログラムID</label><br>
            <input type="text" wire:model="experience_program_id" id="experience_program_id">
        </p>
    </form>
    <form wire:submit="store">
        <p>
            <label for="reservation_date">予約日</label><br>
            <input type="text" wire:model="reservation_date" id="reservation_date">
        </p>
    </form>
    <form wire:submit="store">
        <p>
            <label for="reservation_time">予約時刻</label><br>
            <input type="text" wire:model="reservation_time" id="reservation_time">
        </p>
    </form>
    <form wire:submit="store">
        <p>
            <label for="customer_name">予約者名</label><br>
            <input type="text" wire:model="customer_name" id="customer_name">
        </p>
    </form>
    <form wire:submit="store">
        <p>
            <label for="customer_phone">電話番号</label><br>
            <input type="text" wire:model="customer_phone" id="customer_phone">
        </p>
    </form>
    <form wire:submit="store">
        <p>
            <label for="customer_email">メールアドレス</label><br>
            <input type="text" wire:model="customer_email" id="customer_email">
        </p>
    </form>
    <form wire:submit="store">
        <p>
            <label for="participant_count">参加人数</label><br>
            <input type="text" wire:model="participant_count" id="participant_count">
        </p>
    </form>
    <form wire:submit="store">
        <p>
            <label for="reservation_source">予約経路</label><br>
            <input type="text" wire:model="reservation_source" id="reservation_source">
        </p>
    </form>
    <form wire:submit="store">
        <p>
            <label for="status">ステータス</label><br>
            <input type="text" wire:model="status" id="status">
        </p>
    </form>
    <form wire:submit="store">
        <p>
            <label for="notes">備考</label><br>
            <textarea wire:model="notes" id="notes"></textarea>
        </p>
    </form>
    <button type="submit">登録</button>
</div>
