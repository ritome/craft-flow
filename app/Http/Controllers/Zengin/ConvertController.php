<?php

declare(strict_types=1);

namespace App\Http\Controllers\Zengin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadZenginRequest;
use App\Imports\ZenginImport;
use App\Models\ZenginLog;
use App\Services\ZenginExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 全銀フォーマット変換コントローラ
 *
 * Excel → 全銀フォーマット変換と履歴保存を統合
 */
class ConvertController extends Controller
{
    /**
     * アップロード画面を表示
     *
     * @return View
     */
    public function showUploadForm(): View
    {
        // 前回のセッションをクリア
        session()->forget(['zengin_data', 'zengin_filename']);

        return view('zengin.upload');
    }

    /**
     * プレビュー画面を表示（GETアクセス時 - セッションから復元）
     *
     * @return View|RedirectResponse
     */
    public function showPreview(): View|RedirectResponse
    {
        $validData = session('zengin_data');
        $filename = session('zengin_filename');

        if (! $validData) {
            return redirect()
                ->route('zengin.upload')
                ->withErrors(['excel_file' => 'セッションが切れました。もう一度ファイルをアップロードしてください。']);
        }

        // プレビュー生成
        $exporter = new ZenginExporter;
        $previewData = $exporter->preview($validData);

        // 全データのバリデーション
        $allErrors = [];
        try {
            $exporter->export($validData);
        } catch (\Exception $e) {
            $stats = $exporter->getStats();
            $allErrors = $stats['errors'];
        }

        $skippedCount = session('zengin_skipped_count', 0);
        
        return view('zengin.preview', [
            'previewData' => $previewData,
            'totalCount' => count($validData),
            'errorCount' => count($allErrors),
            'allErrors' => $allErrors,
            'filename' => $filename,
            'skippedCount' => $skippedCount,
        ]);
    }

    /**
     * アップロードされたExcelをプレビュー
     *
     * @param  UploadZenginRequest  $request
     * @return View|RedirectResponse
     */
    public function preview(UploadZenginRequest $request): View|RedirectResponse
    {
        try {
            // Excelファイル読み込み
            $file = $request->file('excel_file');
            $importedData = Excel::toArray(new ZenginImport, $file);

            if (empty($importedData) || empty($importedData[0])) {
                return back()->withErrors([
                    'excel_file' => 'Excelファイルにデータが見つかりませんでした。',
                ]);
            }

            // 最初のシート
            $allRows = $importedData[0];

            if (count($allRows) < 2) {
                return back()->withErrors([
                    'excel_file' => 'Excelファイルにデータが見つかりませんでした。ヘッダー行と最低1行のデータが必要です。',
                ]);
            }

            // ヘッダー行を取得
            $headers = array_shift($allRows);

            // データ行をヘッダーでマップ（空白行はスキップ）
            $formattedData = [];
            foreach ($allRows as $row) {
                $rowData = [];
                foreach ($headers as $index => $header) {
                    $rowData[$header] = $row[$index] ?? null;
                }
                
                // 空白行チェック：主要フィールドがすべて空の場合はスキップ
                $bankCode = $rowData['金融機関コード'] ?? $rowData['bank_code'] ?? '';
                $branchCode = $rowData['支店コード'] ?? $rowData['branch_code'] ?? '';
                $accountNumber = $rowData['口座番号'] ?? $rowData['account_number'] ?? '';
                $recipientName = $rowData['口座名義（カナ）'] ?? $rowData['account_holder'] ?? '';
                $amount = $rowData['振込金額'] ?? $rowData['amount'] ?? '';
                
                // すべて空白の場合はスキップ
                if (empty(trim((string) $bankCode)) && 
                    empty(trim((string) $branchCode)) && 
                    empty(trim((string) $accountNumber)) && 
                    empty(trim((string) $recipientName)) && 
                    empty(trim((string) $amount))) {
                    continue;
                }
                
                $formattedData[] = $rowData;
            }

            // セッションに保存
            $skippedCount = count($allRows) - count($formattedData);
            session([
                'zengin_data' => $formattedData,
                'zengin_filename' => $file->getClientOriginalName(),
                'zengin_skipped_count' => $skippedCount,
            ]);

            // プレビュー生成（表示用）
            $exporter = new ZenginExporter;
            $previewData = $exporter->preview($formattedData);

            // 全データのバリデーション
            $allErrors = [];
            try {
                $exporter->export($formattedData);
            } catch (\Exception $e) {
                $stats = $exporter->getStats();
                $allErrors = $stats['errors'];
            }

            return view('zengin.preview', [
                'previewData' => $previewData,
                'totalCount' => count($formattedData),
                'errorCount' => count($allErrors),
                'allErrors' => $allErrors,
                'filename' => $file->getClientOriginalName(),
                'skippedCount' => $skippedCount,
            ]);
        } catch (\Exception $e) {
            \Log::error('Preview error: '.$e->getMessage());

            return back()->withErrors([
                'excel_file' => 'ファイルの読み込み中にエラーが発生しました: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * 全銀フォーマットに変換してダウンロード（履歴保存あり）
     *
     * @return StreamedResponse|RedirectResponse
     */
    public function convert(): StreamedResponse|RedirectResponse
    {
        \Log::info('=== 変換処理開始 ===');
        
        // セッションからデータ取得
        $validData = session('zengin_data');
        $originalFilename = session('zengin_filename');
        
        \Log::info('セッションデータ取得', [
            'has_data' => !empty($validData),
            'data_count' => is_array($validData) ? count($validData) : 0,
            'filename' => $originalFilename,
        ]);

        if (! $validData) {
            return back()->withErrors([
                'excel_file' => 'セッションが切れました。もう一度ファイルをアップロードしてください。',
            ]);
        }

        try {
            \Log::info('エクスポーター初期化');
            $exporter = new ZenginExporter;

            \Log::info('全銀フォーマット生成開始', ['row_count' => count($validData)]);
            // 全銀フォーマット生成（Shift-JIS + CRLF）
            $content = $exporter->export($validData);
            
            \Log::info('全銀フォーマット生成完了', ['content_length' => strlen($content)]);
            $stats = $exporter->getStats();
            \Log::info('統計情報取得', $stats);

            // ファイル名生成
            $filename = str_replace('{Ymd_His}', date('Ymd_His'), config('zengin.filename_template'));
            $storagePath = config('zengin.storage_path').'/'.$filename;

            \Log::info('ファイル保存開始', ['path' => $storagePath]);
            // ストレージに保存
            Storage::disk('local')->put($storagePath, $content);
            \Log::info('ファイル保存完了');

            // ★履歴に記録（Issue #3統合）★
            \Log::info('履歴保存開始');
            ZenginLog::create([
                'filename' => $filename,
                'file_path' => $storagePath,
                'total_count' => $stats['total_count'],
                'total_amount' => $stats['total_amount'],
            ]);
            \Log::info('履歴保存完了');

            // セッションをクリア
            session()->forget(['zengin_data', 'zengin_filename', 'zengin_skipped_count']);
            \Log::info('セッションクリア完了');

            // ダウンロード
            \Log::info('ダウンロード開始', ['filename' => $filename]);
            return response()->streamDownload(function () use ($content) {
                echo $content;
            }, $filename, [
                'Content-Type' => 'text/plain; charset=shift_jis',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        } catch (\Exception $e) {
            \Log::error('Zengin conversion error: '.$e->getMessage());

            return back()->withErrors([
                'excel_file' => '変換中にエラーが発生しました: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * デバッグ情報表示
     */
    public function debug(): View
    {
        return view('zengin.debug');
    }

    /**
     * セッションクリア（デバッグ用）
     */
    public function debugClearSession(): RedirectResponse
    {
        session()->flush();

        return redirect()->route('zengin.debug')->with('success', 'セッションをクリアしました');
    }
}

