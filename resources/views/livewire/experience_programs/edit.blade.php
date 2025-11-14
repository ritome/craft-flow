<?php

use function Livewire\Volt\{state, mount};
use App\Models\ExperienceProgram;

// フォームの状態を管理
state(['experience_programs', 'name', 'description', 'duration', 'capacity', 'price']);

// ルートモデルバインディングはmountでまとめて行う
mount(function (ExperienceProgram $experience_programs) {
    $this->experience_programs = $experience_programs;
    $this->name = $experience_programs->name;
    $this->description = $experience_programs->description;
    $this->duration = $experience_programs->duration;
    $this->capacity = $experience_programs->capacity;
    $this->price = $experience_programs->price;
});

$update = function () {
    $this->experience_programs->update($this->all());
    return redirect()->route('experience_programs.show', $this->experience_programs);
};

?>

<div>
    <a href="{{ route('experience_programs.index') }}">戻る</a>
    <h1>更新</h1>
    <form wire:submit="update">
        <p>
            <label for="name">プログラム名</label><br>
            <input type="text" wire:model="name" id="name">
        </p>

        <p>
            <label for="description">説明</label><br>
            <textarea wire:model="description" id="description"></textarea>
        </p>

        <p>
            <label for="duration">所要時間</label><br>
            <input type="text" wire:model="duration" id="duration">
        </p>

        <p>
            <label for="capacity">最大受入人数</label><br>
            <input type="text" wire:model="capacity" id="capacity">
        </p>

        <p>
            <label for="price">料金</label><br>
            <input type="text" wire:model="price" id="price">
        </p>

        <button type="submit">更新</button>
    </form>
</div>
