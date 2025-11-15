<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
});

Volt::route('/experience_programs', 'experience_programs.index')->name('experience_programs.index');
Volt::route('/reservations', 'reservations.index')->name('reservations.index');

// 具体的なパスを先に定義
// 新規作成ページ
Volt::route('/experience_programs/create', 'experience_programs.create')->name('experience_programs.create');
Volt::route('/reservations/create', 'reservations.create')->name('reservations.create');
// 動的パラメータを含むルートは最後に定義

// 詳細ページ
Volt::route('/experience_programs/{experience_programs}', 'experience_programs.show')->name('experience_programs.show');

Volt::route('/reservations/{reservations}', 'reservations.show')->name('reservations.show');

// 編集ページ
Volt::route('/experience_programs/{experience_programs}/edit', 'experience_programs.edit')->name('experience_programs.edit');

// 編集ページ

Volt::route('/reservations/{reservation}/edit', 'reservations.edit')->name('reservations.edit');

Route::prefix('reservations')->group(function () {
    // 1. 予約参照/カレンダー (Select.php) - /reservations
    Volt::route('/select', 'reservations.select');

    // 2. 新規予約登録 (Insert.php) - /reservations/new
    Volt::route('/new', 'reservations.insert');

    // 3. 予約更新/編集 (Update.php) - /reservations/{reservationId}/edit
    // **このルートが $reservationId をコンポーネントに渡します。**
    Volt::route('/{reservationId}/edit', 'reservations.update');

    // 4. 予約削除/キャンセル (Delete.php) - /reservations/{reservationId}/delete
    Volt::route('/{reservationId}/delete', 'reservations.delete');
});


// --- 体験プログラム管理 (Programs) ルーティング (Placeholder) ---
Route::prefix('experience_programs')->group(function () {
    Volt::route('/delete', 'experience_programs.delete')->name('experience_programs.delete');
    Volt::route('/insert', 'experience_programs.insert')->name('experience_programs.insert');
    Volt::route('/update', 'experience_programs.update')->name('experience_programs.update');
    Volt::route('/select', 'experience_programs.select')->name('experience_programs.select');
});
