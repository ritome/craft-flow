<?php

use function Livewire\Volt\{state};
use App\Models\ExperienceProgram;
//
state(['name', 'description', 'duration', 'capacity', 'price']);

// メモを保存する関数
$store = function () {
    // フォームからの入力値をデータベースへ保存
    ExperienceProgram::create($this->all());
    // 一覧ページにリダイレクト
    return redirect()->route('experience_programs.index');
};
?>

<div>
    <a href="{{ route('experience_programs.index') }}">戻る</a>
    <h1>新規登録</h1>
    <form wire:submit="store">
        <p>
            <label for="name">プログラム名</label><br>
            <input type="text" wire:model="name" id="name">
        </p>
    </form>
    <form wire:submit="store">
        <p>
            <label for="description">説明</label><br>
            <textarea wire:model="description" id="description"></textarea>
        </p>
    </form>
    <form wire:submit="store">
        <p>
            <label for="duration">所要時間</label><br>
            <input type="text" wire:model="duration" id="duration">
        </p>
    </form>
    <form wire:submit="store">
        <p>
            <label for="capacity">最大受入人数</label><br>
            <input type="text" wire:model="capacity" id="capacity">
        </p>
    </form>
    <form wire:submit="store">
        <p>
            <label for="price">料金</label><br>
            <input type="text" wire:model="price" id="price">
        </p>
    </form>
    <button type="submit">登録</button>
</div>
