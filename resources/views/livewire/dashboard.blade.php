<?php

use function Livewire\Volt\title;

// ブラウザのタブに表示されるタイトルを設定します
title('予約システム');

?>
<div class="bg-gray-100 min-h-screen p-4 sm:p-6 md:p-8">
    <div class="max-w-4xl mx-auto">

        <!-- ページヘッダー -->
        <div class="text-center mb-10 md:mb-12">
            <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-800 tracking-tight">予約管理ダッシュボード</h1>
            <p class="mt-3 text-base sm:text-lg text-gray-500">操作したい項目を選択してください。</p>
        </div>

        <!-- リンクカードのグリッド -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">

            <!-- プログラム管理カード (染め物風) -->
            <a href="{{ route('experience_programs.index') }}"
                class="group relative block p-6 sm:p-8 bg-white border border-gray-200 rounded-2xl shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all duration-300">

                <!-- アイコン (藍染めをイメージした色と、布や絵筆を連想させるアイコンに変更) -->
                <div class="p-4 bg-blue-900/10 rounded-xl inline-block mb-4">
                    <svg class="h-8 w-8 text-blue-800" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.471-2.471a1.125 1.125 0 0 0-1.59-1.59L9.828 13.586a1.125 1.125 0 0 0 0 1.59l1.59 1.59ZM11.42 15.17 8.61 17.98a2.652 2.652 0 0 1-3.75 0L3 16.12a2.652 2.652 0 0 1 0-3.75l2.81-2.81" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m14.25 6.75 4.5-4.5m-4.5 4.5-4.5-4.5m4.5 4.5v-4.5m0 4.5h-4.5" />
                    </svg>
                </div>

                <h2 class="mb-2 text-xl font-bold tracking-tight text-gray-900">プログラム管理</h2>
                <p class="font-normal text-gray-600">染め物などの体験プログラムの追加、編集、削除を行います。</p>

                <!-- 右上の矢印アイコン (色を合わせる) -->
                <span
                    class="absolute top-6 right-6 text-gray-300 group-hover:text-blue-800 transition-colors duration-300">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
                    </svg>
                </span>
            </a>


            <!-- 予約管理カード -->
            <a href="{{ route('reservations.index') }}"
                class="group relative block p-6 sm:p-8 bg-white border border-gray-200 rounded-2xl shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all duration-300">

                <!-- アイコン -->
                <div class="p-4 bg-teal-100 rounded-xl inline-block mb-4">
                    <svg class="h-8 w-8 text-teal-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0h18M12 15.75h.008v.008H12v-.008Z" />
                    </svg>
                </div>

                <h2 class="mb-2 text-xl font-bold tracking-tight text-gray-900">予約管理</h2>
                <p class="font-normal text-gray-600">顧客からの予約の確認、編集、新規登録などを行います。</p>

                <!-- 右上の矢印アイコン -->
                <span
                    class="absolute top-6 right-6 text-gray-300 group-hover:text-teal-600 transition-colors duration-300">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
                    </svg>
                </span>
            </a>

        </div>
    </div>
</div>
