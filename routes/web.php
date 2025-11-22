<?php

declare(strict_types=1);

use App\Http\Controllers\PdfImportController;
use App\Http\Controllers\SettlementController;
use App\Http\Controllers\Zengin\ConvertController;
use App\Http\Controllers\Zengin\HistoryController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// トップページは委託精算書へリダイレクト
Route::get('/', function () {
    return redirect()->route('settlements.index');
});

// 全銀フォーマット変換機能のルート
Route::prefix('zengin')->name('zengin.')->group(function () {
    // アップロード・変換（Issue #5）
    Route::get('/upload', [ConvertController::class, 'showUploadForm'])->name('upload');
    Route::get('/preview', [ConvertController::class, 'showPreview'])->name('preview.show');
    Route::post('/preview', [ConvertController::class, 'preview'])->name('preview');
    Route::post('/convert', [ConvertController::class, 'convert'])->name('convert');

    // 変換履歴
    Route::get('/history', [HistoryController::class, 'index'])->name('history');
    Route::get('/download/{log}', [HistoryController::class, 'download'])->name('download');
    Route::delete('/history/{log}', [HistoryController::class, 'destroy'])->name('history.destroy');
});

// 委託精算書一括発行機能のルート（Issue #12〜#17）
Route::prefix('settlements')->name('settlements.')->group(function () {
    // Issue #12: 精算トップ画面（アップロード）
    Route::get('/', [SettlementController::class, 'index'])->name('index');

    // Issue #12〜#16: 精算書生成処理
    Route::post('/generate', [SettlementController::class, 'generate'])->name('generate');

    // Issue #17: 精算履歴一覧
    Route::get('/history', [SettlementController::class, 'history'])->name('history');

    // Issue #17: Excel ダウンロード
    Route::get('/download/{settlement}/excel', [SettlementController::class, 'downloadExcel'])->name('download.excel');

    // Issue #17: PDF ダウンロード
    Route::get('/download/{settlement}/pdf', [SettlementController::class, 'downloadPdf'])->name('download.pdf');

    // 精算履歴削除
    Route::delete('/{settlement}', [SettlementController::class, 'destroy'])->name('destroy');
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

// PDFインポート
Route::get('/pdf/import', [PdfImportController::class, 'showUploadForm'])->name('pdf.upload.form');
Route::post('/pdf/import', [PdfImportController::class, 'import'])->name('pdf.import');
Route::get('/pdf/history', [PdfImportController::class, 'showHistory'])->name('pdf.history');

// // レジデータアップロード画面
// Volt::route('/pos/upload', 'pos_data.upload')->name('pos.upload');
// // レジデータ一覧
// Volt::route('/pos', 'pos_data.index')->name('pos.index');
// // レジデータ詳細
// Volt::route('/pos/{id}', 'pos_data.show')->name('pos.show');

// --- 体験プログラム管理 (Programs) ルーティング (Placeholder) ---
Route::prefix('experience_programs')->group(function () {
    Volt::route('/delete', 'experience_programs.delete')->name('experience_programs.delete');
    Volt::route('/insert', 'experience_programs.insert')->name('experience_programs.insert');
    Volt::route('/update', 'experience_programs.update')->name('experience_programs.update');
    Volt::route('/select', 'experience_programs.select')->name('experience_programs.select');
});


Volt::route('/dashboard', 'dashboard')->name('dashboard');
