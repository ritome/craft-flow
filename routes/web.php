<?php

declare(strict_types=1);

use App\Http\Controllers\SettlementController;
use App\Http\Controllers\Zengin\ConvertController;
use App\Http\Controllers\Zengin\HistoryController;
use Illuminate\Support\Facades\Route;

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
