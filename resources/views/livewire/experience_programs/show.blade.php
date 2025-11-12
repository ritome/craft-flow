<?php

use function Livewire\Volt\{state};
use App\Models\ExperienceProgram;

// ルートモデルバインディング
state(['experience_programs' => fn(ExperienceProgram $experience_programs) => $experience_programs]);

?>

<div>
    <h1>{{ $experience_programs->name }}</h1>
    <p>{!! nl2br(e($experience_programs->description)) !!}</p>
    <p><strong>所要時間:</strong> {{ $experience_programs->duration }}分</p>
    <p><strong>最大受入人数:</strong> {{ $experience_programs->capacity }}</p>
    <p><strong>料金:</strong> {{ $experience_programs->price }}円</p>
</div>
