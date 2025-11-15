<?php

use function Livewire\Volt\title;

// ブラウザのタブに表示されるタイトルを設定します
title('予約システム');

?>

<div>
    {{-- ページのメインタイトル --}}
    <h1 class="text-2xl font-bold mb-8">管理ダッシュボード</h1>

    {{-- リンクをグリッドレイアウトで表示 --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- プログラム管理へのリンクカード -->
        <a href="{{ route('experience_programs.index') }}"
            class="block p-6 bg-white border border-gray-200 rounded-lg shadow-md hover:bg-gray-100 transition">
            <h2 class="mb-2 text-xl font-semibold tracking-tight text-gray-900">プログラム管理</h2>
            <p class="font-normal text-gray-600">体験プログラムの追加、編集、削除などを行います。</p>
        </a>

        <!-- 予約管理へのリンクカード -->
        <a href="{{ route('reservations.index') }}"
            class="block p-6 bg-white border border-gray-200 rounded-lg shadow-md hover:bg-gray-100 transition">
            <h2 class="mb-2 text-xl font-semibold tracking-tight text-gray-900">予約管理</h2>
            <p class="font-normal text-gray-600">顧客からの予約の確認、編集、新規登録などを行います。</p>
        </a>

    </div>
</div>
