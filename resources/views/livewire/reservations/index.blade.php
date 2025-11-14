<?php

use function Livewire\Volt\{state};
use App\Models\Reservation;

state(['reservations' => fn() => Reservation::all()]);

$create = function () {
    return redirect()->route('reservations.create');
};
?>

<div>
    <h1>予約一覧</h1>
    <ul>
        @foreach ($reservations as $reservation)
            <li>
                <a href="{{ route('reservations.show', $reservation) }}">
                    {{ $reservation->customer_name }}
                </a>
            </li>
        @endforeach
    </ul>

    <button wire:click="create">予約登録する</button>
</div>
