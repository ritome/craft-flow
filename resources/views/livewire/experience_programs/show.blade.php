<?php

use function Livewire\Volt\{state};
use App\Models\ExperienceProgram;

// ルートモデルバインディング
state(['experience_programs' => fn(ExperienceProgram $experience_programs) => $experience_programs]);

// 編集ページにリダイレクト
$edit = function () {
    // 編集ページにリダイレクト
    return redirect()->route('experience_programs.edit', $this->experience_programs);
};

$destroy = function () {
    $this->experience_programs->delete();
    return redirect()->route('experience_programs.index');
};
?>

<div>
    <h1>{{ $experience_programs->name }}</h1>
    <p>{!! nl2br(e($experience_programs->description)) !!}</p>
    <p><strong>所要時間:</strong> {{ $experience_programs->duration }}分</p>
    <p><strong>最大受入人数:</strong> {{ $experience_programs->capacity }}</p>
    <p><strong>料金:</strong> {{ $experience_programs->price }}円</p>
    <button wire:click="edit">編集する</button>
    <button wire:click="destroy" wire:confirm="本当に削除しますか？">削除する</button>
</div>
