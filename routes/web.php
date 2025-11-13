<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Zengin\ConvertController;

Route::get('/', function () {
    return view('welcome');
});

// 全銀フォーマット変換機能のルート
Route::prefix('zengin')->group(function () {
    // アップロード画面を表示
    Route::get('/upload', [ConvertController::class, 'showUploadForm'])->name('zengin.upload');
    
    // Excel ファイルを受け取って変換処理を実行
    Route::post('/convert', [ConvertController::class, 'convert'])->name('zengin.convert');
    
    // 変換後のファイルをダウンロード
    Route::get('/download/{filename}', [ConvertController::class, 'download'])->name('zengin.download');
});
