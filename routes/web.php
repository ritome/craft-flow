<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
});

Volt::route('/experience_programs', 'experience_programs.index')->name('experience_programs.index');
Volt::route('/reservations', 'reservations.index')->name('reservations.index');

// 詳細ページ
Volt::route('/experience_programs/{experience_programs}', 'experience_programs.show')->name('experience_programs.show');

Volt::route('/reservations/{reservations}', 'reservations.show')->name('reservations.show');


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
