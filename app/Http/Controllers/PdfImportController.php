<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PdfImportRequest;
use App\Models\ImportHistory;
use App\Services\PdfImportService;
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
    ) {}

    /**
     * アップロードフォームを表示
     */
    public function showUploadForm(): View
    {
        // TODO: 実装
        return view('upload');
    }

    /**
     * PDFファイルをインポート
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
                $safeFileName = uniqid('pdf_').'.'.$extension;

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
                if (! File::exists($fullPath)) {
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
            $result = $this->pdfImportService->import($pdfPaths);
            $excelPath = $result['excel_path'];
            $historyId = $result['history_id'];

            // 一時ファイルの削除
            $this->cleanupTempFiles($pdfPaths);

            Log::info('集計履歴を保存しました', [
                'history_id' => $historyId,
                'excel_path' => $excelPath,
            ]);

            // Excelファイルをダウンロードレスポンスとして返す
            // 履歴用にファイルを残すため、deleteFileAfterSendをfalseに変更
            return response()->download(
                $excelPath,
                basename($excelPath),
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            )->deleteFileAfterSend(false);
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
     */
    public function showHistory(): View
    {
        $histories = ImportHistory::orderBy('import_date', 'desc')
            ->paginate(20);

        return view('history', compact('histories'));
    }

    /**
     * 過去の集計結果を再ダウンロード
     */
    public function download(ImportHistory $history): BinaryFileResponse
    {
        if (! $history->excelFileExists()) {
            abort(404, 'ファイルが見つかりません。');
        }

        $filePath = $history->getExcelFullPath();
        $fileName = '売上集計_'.$history->import_date->format('Ymd_His').'.xlsx';

        return response()->download(
            $filePath,
            $fileName,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * 履歴を削除（ファイルも削除）
     */
    public function destroy(ImportHistory $history): \Illuminate\Http\RedirectResponse
    {
        // Excelファイルを削除
        if ($history->excelFileExists()) {
            Storage::disk('local')->delete($history->excel_path);
        }

        // 履歴レコードを削除
        $history->delete();

        return redirect()->route('pdf.history')
            ->with('success', '履歴を削除しました。');
    }

    /**
     * 一時ファイルをクリーンアップ
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
