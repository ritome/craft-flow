<?php

use App\Http\Controllers\PdfImportController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
});

// PDFインポート
Route::get('/pdf/import', [PdfImportController::class, 'showUploadForm'])->name('pdf.upload.form');
Route::post('/pdf/import', [PdfImportController::class, 'import'])->name('pdf.import');
Route::get('/pdf/history', [PdfImportController::class, 'showHistory'])->name('pdf.history');

// レジデータアップロード画面
Volt::route('/pos/upload', 'pos_data.upload')->name('pos.upload');
// レジデータ一覧
Volt::route('/pos', 'pos_data.index')->name('pos.index');
// レジデータ詳細
Volt::route('/pos/{id}', 'pos_data.show')->name('pos.show');
