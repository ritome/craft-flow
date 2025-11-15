<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
});

// レジデータアップロード画面
Volt::route('/pos/upload', 'pos_data.upload')->name('pos.upload');
// レジデータ一覧
Volt::route('/pos', 'pos_data.index')->name('pos.index');
// レジデータ詳細
Volt::route('/pos/{id}', 'pos_data.show')->name('pos.show');
