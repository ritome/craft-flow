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
use Illuminate\Http\UploadedFile;

/**
 * PDFインポート機能のコントローラー
 */
class PdfImportController extends Controller
{
    public function __construct(
        private readonly PdfImportService $pdfImportService
    ) {}

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
     * @return BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function import(PdfImportRequest $request): BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        Log::info('=== PDFインポート処理開始 ===');
        Log::info('リクエストデータ', [
            'has_pdf_files' => $request->hasFile('pdf_files'),
            'all_files' => $request->allFiles(),
        ]);

        try {
            $uploadedFiles = $request->file('pdf_files');

            Log::info('アップロードファイル取得', [
                'uploaded_files_count' => is_array($uploadedFiles) ? count($uploadedFiles) : 0,
                'uploaded_files_type' => gettype($uploadedFiles),
            ]);

            $pdfPaths = [];

            // アップロードされたファイルを一時保存
            foreach ($uploadedFiles as $file) {
                // 日本語ファイル名の問題を回避するため、英数字のみのファイル名を生成
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $safeFileName = uniqid('pdf_') . '.' . $extension;

                // Storage::putFileAsを使用してファイルを保存
                $tempPath = Storage::disk('local')->putFileAs(
                    'pdf_temp',
                    $file,
                    $safeFileName
                );

                if ($tempPath === false) {
                    throw new \RuntimeException("ファイルの保存に失敗しました: {$originalName}");
                }

                // Storage::disk('local')->path()を使用して正しいフルパスを取得
                // localディスクのrootがstorage/app/privateに設定されているため、これを使用
                $fullPath = Storage::disk('local')->path($tempPath);

                // ファイルが実際に存在するか確認
                if (!File::exists($fullPath)) {
                    throw new \RuntimeException("保存されたファイルが見つかりません: {$fullPath}");
                }

                $pdfPaths[] = $fullPath;

                Log::info('ファイル保存成功', [
                    'original_name' => $originalName,
                    'safe_file_name' => $safeFileName,
                    'temp_path' => $tempPath,
                    'full_path' => $fullPath,
                    'file_is_valid' => $file->isValid(),
                    'file_size' => $file->getSize(),
                    'storage_exists' => Storage::disk('local')->exists($tempPath),
                    'file_exists' => File::exists($fullPath),
                ]);
            }

            Log::info('PDFファイルのアップロード完了', [
                'files_count' => count($pdfPaths),
                'paths' => $pdfPaths,
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
