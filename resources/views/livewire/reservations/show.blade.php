<?php

use function Livewire\Volt\{state};
use App\Models\Reservation;
use App\Models\ExperienceProgram;

// ルートモデルバインディング
state(['reservations' => fn(Reservation $reservations) => $reservations]);

// 編集ページにリダイレクト
$edit = function () {
    // 編集ページにリダイレクト
    return redirect()->route('reservations.edit', $this->reservations);
};

$destroy = function () {
    $this->reservations->delete();
    return redirect()->route('reservations.index');
};

?>

<div>
    <a href="{{ route('reservations.index') }}">戻る</a>
    <h1>予約詳細</h1>
    <div class="mt-4 p-4 border rounded-lg bg-white shadow-sm space-y-2">
        <p><strong>ID:</strong> {{ $reservations->id }}</p>
        <p><strong>プログラムID:</strong> {{ $reservations->experience_program_id }}</p>

        <!-- ★修正点: プログラム名を表示する行 -->
        <p>
            <strong>プログラム名:</strong>
            {{ $reservations->experienceProgram?->name ?? 'プログラム情報なし' }}
        </p>

        <p><strong>予約日:</strong> {{ $reservations->reservation_date }}</p>
        <p><strong>予約時刻:</strong> {{ $reservations->reservation_time }}</p>
        <p><strong>予約者名:</strong> {{ $reservations->customer_name }}</p>
        <p><strong>電話番号:</strong> {{ $reservations->customer_phone }}</p>
        <p><strong>メール:</strong> {{ $reservations->customer_email }}</p>
        <p><strong>参加人数:</strong> {{ $reservations->participant_count }}</p>
        <p><strong>予約経路:</strong> {{ $reservations->reservation_source }}</p>
        <p><strong>予約状態:</strong> {{ $reservations->status }}</p>
        <p><strong>備考:</strong> {{ $reservations->notes }}</p>
        <p><strong>作成日時:</strong> {{ $reservations->created_at }}</p>
        <p><strong>更新日時:</strong> {{ $reservations->updated_at }}</p>
        <p><strong>削除日時:</strong> {{ $reservations->deleted_at }}</p>
        <p><strong>論理削除フラグ:</strong> {{ $reservations->deleted_flg }}</p>
    </div>


    <button wire:click="edit">編集する</button>
    <button wire:click="destroy" wire:confirm="本当に削除しますか？">削除する</button>
</div>
