<?php

use App\Http\Controllers\Zengin\ConvertController;
use App\Http\Controllers\Zengin\HistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('zengin.upload');
});

// 全銀フォーマット変換機能のルート
Route::prefix('zengin')->name('zengin.')->group(function () {
    // アップロード・変換（Issue #1, #2）
    Route::get('/upload', [ConvertController::class, 'showUploadForm'])->name('upload');
    Route::get('/preview', [ConvertController::class, 'showPreview'])->name('preview.show');
    Route::post('/preview', [ConvertController::class, 'preview'])->name('preview');
    Route::post('/convert', [ConvertController::class, 'convert'])->name('convert');

    // 変換履歴（Issue #3）
    Route::get('/history', [HistoryController::class, 'index'])->name('history');
    Route::get('/download/{id}', [HistoryController::class, 'download'])->name('download');
    Route::delete('/history/{id}', [HistoryController::class, 'destroy'])->name('history.destroy');
    
    // デバッグ
    Route::get('/debug', [ConvertController::class, 'debug'])->name('debug');
    Route::post('/debug/clear', [ConvertController::class, 'debugClearSession'])->name('debug.clear');
});
