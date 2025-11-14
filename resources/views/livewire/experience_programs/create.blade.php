<?php

use function Livewire\Volt\{state, rules};
use App\Models\ExperienceProgram;
//
state(['name', 'description', 'duration', 'capacity', 'price']);

// バリデーションルールを定義
rules([
    'name' => 'required|string|max:255',
    'description' => 'required|string|max:2000',
    'duration' => 'required|numeric|min:0',
    'capacity' => 'required|integer|min:1',
    'price' => 'required|numeric|min:0',
]);
// メモを保存する関数
$store = function () {
    $this->validate();
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
            @error('name')
                <span class="error">({{ $message }})</span>
            @enderror
            <br>
            <input type="text" wire:model="name" id="name">
        </p>

        <p>
            @error('description')
                <span class="error">({{ $message }})</span>
            @enderror
            <br>
            <textarea wire:model="description" id="description"></textarea>
        </p>

        <p>
            @error('duration')
                <span class="error">({{ $message }})</span>
            @enderror
            <br>
            <input type="text" wire:model="duration" id="duration">
        </p>
        <p>
            @error('capacity')
                <span class="error">({{ $message }})</span>
            @enderror
            <br>
            <input type="text" wire:model="capacity" id="capacity">
        </p>
        <p>
            @error('price')
                <span class="error">({{ $message }})</span>
            @enderror
            <br>
            <input type="text" wire:model="price" id="price">
        </p>

        <button type="submit">登録</button>
    </form>
</div>
