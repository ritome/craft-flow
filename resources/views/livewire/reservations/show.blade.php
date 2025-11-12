<?php

use function Livewire\Volt\{state};
use App\Models\Reservation;
// ルートモデルバインディング
state(['reservations' => fn(Reservation $reservations) => $reservations]);

?>

<div>
    <a href="{{ route('reservations.index') }}">戻る</a>
    <h1>{{ $reservations->customer_name }}</h1>
    <p>{!! nl2br(e($reservations->customer_phone)) !!}</p>
    <p><strong>メール:</strong> {{ $reservations->customer_email }}</p>
    <p><strong>参加人数:</strong> {{ $reservations->participant_count }}</p>
    <p><strong>予約経路:</strong> {{ $reservations->reservation_source }}</p>
    <p><strong>ステータス:</strong> {{ $reservations->status }}</p>
    <p><strong>備考:</strong> {{ $reservations->notes }}</p>


</div>
