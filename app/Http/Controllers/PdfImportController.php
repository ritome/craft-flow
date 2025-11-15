<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PdfImportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * PDFインポート機能のコントローラー
 */
class PdfImportController extends Controller
{
    public function __construct(
        private readonly PdfImportService $pdfImportService
    ) {
    }

    /**
     * アップロードフォームを表示
     *
     * @return View
     */
    public function showUploadForm(): View
    {
        // TODO: 実装
        return view('upload');
    }

    /**
     * PDFファイルをインポート
     *
     * @param Request $request
     * @return Response
     */
    public function import(Request $request): Response
    {
        // TODO: 実装
        // バリデーション
        // サービス呼び出し
        // レスポンス返却
        return response()->noContent();
    }

    /**
     * インポート履歴を表示
     *
     * @return View
     */
    public function showHistory(): View
    {
        // TODO: 実装
        return view('history');
    }
}

