<?php

use function Livewire\Volt\{state, title};
use Carbon\Carbon;

// ブラウザのタブに表示されるタイトル
title('業務統合ポータル');

// ★修正点: state() を使って、画面で使う変数を明確に定義します
state([
    'today' => fn() => Carbon::now()->isoFormat('Y年M月D日 (ddd)'),
]);

?>

<div class="bg-gray-50 min-h-screen">

    <!-- ★★★ 藍色スタイルのヘッダー ★★★ -->
    <div class="relative bg-indigo-900 text-white shadow-lg mb-10 overflow-hidden">
        <!-- 背景の和柄っぽいアクセント -->
        <div class="absolute top-0 right-0 -mr-10 -mt-10 w-40 h-40 rounded-full bg-white opacity-5 blur-2xl"></div>
        <div class="absolute bottom-0 left-0 -ml-10 -mb-10 w-40 h-40 rounded-full bg-teal-500 opacity-10 blur-2xl"></div>

        <div class="relative max-w-4xl mx-auto px-6 py-12 text-center">
            <p class="text-indigo-200 text-xs font-bold tracking-[0.3em] uppercase mb-2">Craft Flow System</p>
            <h1 class="text-3xl md:text-4xl font-bold tracking-wider">
                社内業務システム
            </h1>
            <div class="mt-4 w-12 h-1 bg-teal-400 mx-auto rounded-full"></div>
            <p class="mt-4 text-indigo-100 text-sm font-light">
                {{ $today }}
            </p>
        </div>
    </div>

    <!-- メインコンテンツ -->
    <div class="max-w-4xl mx-auto px-6 pb-12">

        <!-- メニューグリッド -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            <!-- 1. 委託精算書一括発行 -->
            <a href="{{ route('settlements.index') }}"
                class="group relative flex flex-col md:flex-row items-start p-5 bg-white border border-gray-100 rounded-2xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                <div
                    class="absolute top-0 right-0 w-20 h-20 bg-blue-50 rounded-bl-full -mr-10 -mt-10 transition-transform group-hover:scale-150 duration-500">
                </div>

                <div
                    class="relative z-10 p-3 bg-blue-50 text-blue-600 rounded-xl mb-3 md:mb-0 md:mr-4 flex-shrink-0 group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </div>

                <div class="relative z-10 text-center md:text-left">
                    <h2 class="text-base font-bold text-gray-800 mb-1 group-hover:text-blue-700 transition-colors">
                        委託精算書一括発行
                    </h2>
                    <p class="text-xs text-gray-500 leading-relaxed">
                        委託販売などの精算書データを一括で作成・PDF発行します。
                    </p>
                </div>
            </a>

            <!-- 2. 予約管理ダッシュボード -->
            <a href="{{ route('dashboard') }}"
                class="group relative flex flex-col md:flex-row items-start p-5 bg-white border border-gray-100 rounded-2xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                <div
                    class="absolute top-0 right-0 w-20 h-20 bg-teal-50 rounded-bl-full -mr-10 -mt-10 transition-transform group-hover:scale-150 duration-500">
                </div>

                <div
                    class="relative z-10 p-3 bg-teal-50 text-teal-600 rounded-xl mb-3 md:mb-0 md:mr-4 flex-shrink-0 group-hover:bg-teal-600 group-hover:text-white transition-colors duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0h18M12 15.75h.008v.008H12v-.008Z" />
                    </svg>
                </div>

                <div class="relative z-10 text-center md:text-left">
                    <h2 class="text-base font-bold text-gray-800 mb-1 group-hover:text-teal-700 transition-colors">
                        予約管理ダッシュボード
                    </h2>
                    <p class="text-xs text-gray-500 leading-relaxed">
                        体験プログラムの予約状況確認、統計分析、プログラム管理を行います。
                    </p>
                </div>
            </a>

            <!-- 3. 全銀フォーマット変換 -->
            <a href="{{ route('zengin.upload') }}"
                class="group relative flex flex-col md:flex-row items-start p-5 bg-white border border-gray-100 rounded-2xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                <div
                    class="absolute top-0 right-0 w-20 h-20 bg-purple-50 rounded-bl-full -mr-10 -mt-10 transition-transform group-hover:scale-150 duration-500">
                </div>

                <div
                    class="relative z-10 p-3 bg-purple-50 text-purple-600 rounded-xl mb-3 md:mb-0 md:mr-4 flex-shrink-0 group-hover:bg-purple-600 group-hover:text-white transition-colors duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                    </svg>
                </div>

                <div class="relative z-10 text-center md:text-left">
                    <h2 class="text-base font-bold text-gray-800 mb-1 group-hover:text-purple-700 transition-colors">
                        全銀フォーマット変換
                    </h2>
                    <p class="text-xs text-gray-500 leading-relaxed">
                        振込データをアップロードし、銀行振込用の全銀データ形式へ変換します。
                    </p>
                </div>
            </a>

            <!-- 4. レジデータ自動集計システム -->
            <a href="{{ route('pdf.upload.form') }}"
                class="group relative flex flex-col md:flex-row items-start p-5 bg-white border border-gray-100 rounded-2xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                <div
                    class="absolute top-0 right-0 w-20 h-20 bg-orange-50 rounded-bl-full -mr-10 -mt-10 transition-transform group-hover:scale-150 duration-500">
                </div>

                <div
                    class="relative z-10 p-3 bg-orange-50 text-orange-600 rounded-xl mb-3 md:mb-0 md:mr-4 flex-shrink-0 group-hover:bg-orange-600 group-hover:text-white transition-colors duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                </div>

                <div class="relative z-10 text-center md:text-left">
                    <h2 class="text-base font-bold text-gray-800 mb-1 group-hover:text-orange-700 transition-colors">
                        レジデータ自動集計
                    </h2>
                    <p class="text-xs text-gray-500 leading-relaxed">
                        レジから出力されたPDFを取り込み、売上データを自動で解析・集計します。
                    </p>
                </div>
            </a>

        </div>
    </div>
</div>
