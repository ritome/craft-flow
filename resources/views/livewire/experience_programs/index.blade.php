<?php

use function Livewire\Volt\{state};
use App\Models\ExperienceProgram;

// ルートモデルバインディング
state(['experience_programs' => fn() => ExperienceProgram::all()]);
?>

<div>
    <h1>プログラム一覧</h1>
    <ul>
        @foreach ($experience_programs as $experience_program)
            <li>
                <a href="{{ route('experience_programs.show', $experience_program) }}">
                    {{ $experience_program->name }}
                </a>
            </li>
        @endforeach
    </ul>
</div>
