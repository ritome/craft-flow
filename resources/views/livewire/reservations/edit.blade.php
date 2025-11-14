<?php

use function Livewire\Volt\{state, mount};
use App\Models\Reservation;
//
// フォームの状態を管理
state(['reservations', 'experience_program_id', 'reservation_date', 'reservation_time', 'customer_name', 'customer_phone', 'customer_email', 'participant_count', 'reservation_source', 'status', 'notes']);

// ルートモデルバインディングはmountでまとめて行う
mount(function (Reservation $reservations) {
    $this->reservations = $reservations;
    $this->experience_program_id = $reservations->experience_program_id;
    $this->reservation_date = $reservations->reservation_date;
    $this->reservation_time = $reservations->reservation_time;
    $this->customer_name = $reservations->customer_name;
    $this->customer_phone = $reservations->customer_phone;
    $this->customer_email = $reservations->customer_email;
    $this->participant_count = $reservations->participant_count;
    $this->reservation_source = $reservations->reservation_source;
    $this->status = $reservations->status;
    $this->notes = $reservations->notes;
});

$update = function () {
    $this->reservations->update($this->all());
    return redirect()->route('reservations.show', $this->reservations);
};
?>

<div>
    <a href="{{ route('reservations.index') }}">戻る</a>
    <h1>更新</h1>
    <form wire:submit="update">
        <p>
            <label for="experience_program_id">プログラムID</label><br>
            <input type="text" wire:model="experience_program_id" id="experience_program_id" disabled>
        </p>

        <p>
            <label for="reservation_date">予約日</label><br>
            <input type="text" wire:model="reservation_date" id="reservation_date">
        </p>


        <p>
            <label for="reservation_time">予約時刻</label><br>
            <input type="text" wire:model="reservation_time" id="reservation_time">
        </p>

        <p>
            <label for="customer_name">予約者名</label><br>
            <input type="text" wire:model="customer_name" id="customer_name">
        </p>

        <p>
            <label for="customer_phone">電話番号</label><br>
            <input type="text" wire:model="customer_phone" id="customer_phone">
        </p>

        <p>
            <label for="customer_email">メールアドレス</label><br>
            <input type="text" wire:model="customer_email" id="customer_email">
        </p>

        <p>
            <label for="participant_count">参加人数</label><br>
            <input type="text" wire:model="participant_count" id="participant_count">
        </p>

        <p>
            <label for="reservation_source">予約経路</label><br>
            <input type="text" wire:model="reservation_source" id="reservation_source">
        </p>

        <p>
            <label for="status">ステータス</label><br>
            <input type="text" wire:model="status" id="status">
        </p>

        <button type="submit">更新</button>
    </form>
</div>
