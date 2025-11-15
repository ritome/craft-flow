<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PdfImportRequest;
use App\Services\PdfImportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
     * @param PdfImportRequest $request
     * @return BinaryFileResponse
     */
    public function import(PdfImportRequest $request): BinaryFileResponse
    {
        try {
            $uploadedFiles = $request->file('pdf_files');
            $pdfPaths = [];

            // アップロードされたファイルを一時保存
            foreach ($uploadedFiles as $file) {
                $tempPath = $file->storeAs(
                    'pdf_temp',
                    uniqid('pdf_').'_'.$file->getClientOriginalName(),
                    'local'
                );
                $pdfPaths[] = storage_path('app/'.$tempPath);
            }

            Log::info('PDFファイルのアップロード完了', [
                'files_count' => count($pdfPaths),
            ]);

            // PDFインポート処理実行
            $excelPath = $this->pdfImportService->import($pdfPaths);

            // 一時ファイルの削除
            $this->cleanupTempFiles($pdfPaths);

            // Excelファイルをダウンロードレスポンスとして返す
            return response()->download(
                $excelPath,
                basename($excelPath),
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            )->deleteFileAfterSend(true);
        } catch (\InvalidArgumentException $e) {
            Log::warning('PDFインポート処理でバリデーションエラー', [
                'error' => $e->getMessage(),
            ]);

            // 一時ファイルのクリーンアップ
            if (isset($pdfPaths)) {
                $this->cleanupTempFiles($pdfPaths);
            }

            return back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        } catch (\Exception $e) {
            Log::error('PDFインポート処理で予期しないエラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 一時ファイルのクリーンアップ
            if (isset($pdfPaths)) {
                $this->cleanupTempFiles($pdfPaths);
            }

            return back()
                ->withErrors(['error' => 'PDFインポート処理中にエラーが発生しました。'])
                ->withInput();
        }
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

    /**
     * 一時ファイルをクリーンアップ
     *
     * @param array $filePaths
     * @return void
     */
    private function cleanupTempFiles(array $filePaths): void
    {
        foreach ($filePaths as $path) {
            if (File::exists($path)) {
                try {
                    File::delete($path);
                    Log::debug('一時ファイルを削除', ['path' => $path]);
                } catch (\Exception $e) {
                    Log::warning('一時ファイルの削除に失敗', [
                        'path' => $path,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}

