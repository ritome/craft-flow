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
 * Issue #5: 全銀フォーマットテキストファイル出力機能
 */
class ConvertController extends Controller
{
    /**
     * アップロード画面表示
     */
    public function showUploadForm(): View
    {
        // セッションをクリア
        session()->forget(['zengin_data', 'zengin_filename', 'zengin_skipped_count']);

        return view('zengin.upload');
    }

    /**
     * Excelファイルアップロード＆プレビュー生成
     */
    public function preview(UploadZenginRequest $request): View|RedirectResponse
    {
        $file = $request->file('excel_file');

        // Excelファイルをインポート
        $importedData = Excel::toArray(new ZenginImport, $file);

        if (empty($importedData) || empty($importedData[0])) {
            return back()->withErrors([
                'excel_file' => 'Excelファイルにデータが見つかりませんでした。ヘッダー行と最低1行のデータが必要です。',
            ]);
        }

        // 最初のシートを取得
        $sheet = $importedData[0];

        if (count($sheet) < 2) {
            return back()->withErrors([
                'excel_file' => 'データ行が見つかりません。ヘッダー行の下に最低1行のデータが必要です。',
            ]);
        }

        // ヘッダー行とデータ行を分離
        $headers = $sheet[0];
        $allRows = array_slice($sheet, 1);

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
            if (
                empty(trim((string) $bankCode)) &&
                empty(trim((string) $branchCode)) &&
                empty(trim((string) $accountNumber)) &&
                empty(trim((string) $recipientName)) &&
                empty(trim((string) $amount))
            ) {
                continue;
            }

            $formattedData[] = $rowData;
        }

        if (empty($formattedData)) {
            return back()->withErrors([
                'excel_file' => '有効なデータ行が見つかりませんでした。',
            ]);
        }

        // セッションに保存
        $skippedCount = count($allRows) - count($formattedData);
        session([
            'zengin_data' => $formattedData,
            'zengin_filename' => $file->getClientOriginalName(),
            'zengin_skipped_count' => $skippedCount,
        ]);

        // プレビュー生成
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
    }

    /**
     * プレビュー画面の直接表示（リロード対応）
     */
    public function showPreview(): View|RedirectResponse
    {
        $validData = session('zengin_data');
        $filename = session('zengin_filename');
        $skippedCount = session('zengin_skipped_count', 0);

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
     * 全銀フォーマット変換＆ダウンロード
     *
     * Issue #5の要件：
     * - セッションから変換対象データ取得
     * - ZenginExporterで全銀フォーマット生成
     * - Shift-JIS + CRLF + 120バイト固定長
     * - storage/app/private/zengin/ に保存
     * - ファイル名: zengin_YYYYMMDD_HHMMSS.txt
     * - HTTPヘッダー: Content-Type: text/plain; charset=shift_jis
     * - ZenginLogに履歴保存
     */
    public function convert(): StreamedResponse|RedirectResponse
    {
        $validData = session('zengin_data');
        $filename = session('zengin_filename');

        if (! $validData) {
            return back()->withErrors([
                'excel_file' => 'セッションが切れました。もう一度ファイルをアップロードしてください。',
            ]);
        }

        try {
            $exporter = new ZenginExporter;

            // 全銀フォーマット生成（Shift-JIS + CRLF + 120バイト固定長）
            $content = $exporter->export($validData);
            $stats = $exporter->getStats();

            // ファイル名生成: zengin_YYYYMMDD_HHMMSS.txt
            $outputFilename = str_replace('{Ymd_His}', date('Ymd_His'), config('zengin.filename_template'));
            $storagePath = config('zengin.storage_path').'/'.$outputFilename;

            // storage/app/private/zengin/ に保存
            Storage::disk('local')->put($storagePath, $content);

            // ZenginLogに履歴保存
            ZenginLog::create([
                'filename' => $outputFilename,
                'file_path' => $storagePath,
                'total_count' => $stats['total_count'],
                'total_amount' => $stats['total_amount'],
            ]);

            // セッションをクリア
            session()->forget(['zengin_data', 'zengin_filename', 'zengin_skipped_count']);

            // ダウンロード
            // HTTPヘッダー: Content-Type: text/plain; charset=shift_jis
            return response()->streamDownload(function () use ($content) {
                echo $content;
            }, $outputFilename, [
                'Content-Type' => 'text/plain; charset=shift_jis',
                'Content-Disposition' => 'attachment; filename="'.$outputFilename.'"',
            ]);
        } catch (\Exception $e) {
            \Log::error('Zengin conversion error: '.$e->getMessage());

            return back()->withErrors([
                'excel_file' => '変換中にエラーが発生しました: '.$e->getMessage(),
            ]);
        }
    }
}
