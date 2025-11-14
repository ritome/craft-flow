<?php

declare(strict_types=1);

namespace App\Http\Controllers\Zengin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadZenginRequest;
use App\Imports\ZenginImport;
use App\Models\ZenginLog;
use App\Services\ZenginExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 全銀フォーマット変換コントローラ
 *
 * Excel ファイルをアップロードして、
 * 全銀フォーマット風の固定長テキストに変換する機能を提供します。
 */
class ConvertController extends Controller
{
    /**
     * アップロード画面を表示
     */
    public function showUploadForm(): View
    {
        // 全銀フォーマット関連のセッションデータをクリア
        session()->forget(['zengin_data', 'zengin_filename']);

        return view('zengin.upload');
    }

    /**
     * Excel ファイルを受け取ってデータをプレビュー
     */
    public function preview(UploadZenginRequest $request): View|RedirectResponse
    {
        // アップロードされたファイルを取得
        $file = $request->file('excel_file');

        try {
            // Excel ファイルからデータを読み込む（生データとして）
            $importedData = Excel::toArray(new ZenginImport, $file);

            // 最初のシートのデータを取得
            $allRows = $importedData[0] ?? [];

            if (empty($allRows)) {
                return back()->withErrors([
                    'excel_file' => 'Excel ファイルが空です。',
                ])->withInput();
            }

            // 1行目をヘッダー（列名）として取得
            $headers = array_shift($allRows);

            // 列名とデータを組み合わせて連想配列に変換
            $rows = [];
            foreach ($allRows as $row) {
                $associativeRow = [];
                foreach ($headers as $index => $header) {
                    $associativeRow[$header] = $row[$index] ?? null;
                }
                $rows[] = $associativeRow;
            }
        } catch (\Exception $e) {
            \Log::error('Excel import error: '.$e->getMessage());

            return back()->withErrors([
                'excel_file' => 'Excel ファイルの読み込みに失敗しました。ファイル形式を確認してください。',
            ])->withInput();
        }

        // 必須項目を抽出
        $extractedData = [];
        $errorRows = []; // エラー行を記録

        foreach ($rows as $index => $row) {
            // 必須項目のチェック（空の行をスキップ）
            if (empty($row['金融機関名']) && empty($row['口座番号'])) {
                continue;
            }

            $rowData = [
                'row_number' => $index + 2, // Excel上の行番号（ヘッダー分+1）
                'bank_code' => $row['金融機関コード'] ?? '',
                'bank_name' => $row['金融機関名'] ?? '',
                'branch_code' => $row['支店コード'] ?? '',
                'branch_name' => $row['支店名'] ?? '',
                'account_type' => $row['預金種目'] ?? '',
                'account_number' => $row['口座番号'] ?? '',
                'account_holder' => $row['口座名義カナ'] ?? $row['口座名義（カナ）'] ?? '',
                'amount' => $row['振込金額'] ?? '0',
                'customer_name' => $row['事業者名'] ?? '',
            ];

            // 必須項目のバリデーション
            $errors = [];
            if (empty($rowData['bank_code'])) {
                $errors[] = '金融機関コードが未入力';
            }
            if (empty($rowData['bank_name'])) {
                $errors[] = '金融機関名が未入力';
            }
            if (empty($rowData['branch_code'])) {
                $errors[] = '支店コードが未入力';
            }
            if (empty($rowData['branch_name'])) {
                $errors[] = '支店名が未入力';
            }
            if (empty($rowData['account_number'])) {
                $errors[] = '口座番号が未入力';
            }
            if (empty($rowData['account_holder'])) {
                $errors[] = '口座名義が未入力';
            }

            $rowData['errors'] = $errors;
            $rowData['has_error'] = ! empty($errors);

            if (! empty($errors)) {
                $errorRows[] = $rowData;
            }

            $extractedData[] = $rowData;
        }

        // データが空の場合はエラー
        if (empty($extractedData)) {
            return back()->withErrors([
                'excel_file' => 'Excel ファイルにデータが見つかりませんでした。ヘッダー行と最低1行のデータが必要です。',
            ]);
        }

        // データをセッションに保存（変換時に使用）
        session([
            'zengin_data' => $extractedData,
            'zengin_filename' => $file->getClientOriginalName(),
        ]);

        // プレビュー画面を表示
        return view('zengin.preview', [
            'data' => $extractedData,
            'totalCount' => count($extractedData),
            'errorCount' => count($errorRows),
            'originalFilename' => $file->getClientOriginalName(),
        ]);
    }

    /**
     * プレビュー後、変換処理を実行してダウンロード
     */
    public function convert(): StreamedResponse|RedirectResponse
    {
        // セッションからデータを取得
        $extractedData = session('zengin_data');
        $originalFilename = session('zengin_filename');

        if (empty($extractedData)) {
            return redirect()->route('zengin.upload')->withErrors([
                'excel_file' => 'セッションが切れました。もう一度ファイルをアップロードしてください。',
            ]);
        }

        // エラーがある行を除外
        $validData = array_filter($extractedData, function ($row) {
            return ! $row['has_error'];
        });

        if (empty($validData)) {
            return back()->withErrors([
                'conversion' => 'すべての行にエラーがあります。データを修正してください。',
            ]);
        }

        try {
            $exporter = new ZenginExporter;

            // 全銀フォーマットに変換（Shift-JIS、CRLF、120バイト）
            $content = $exporter->export($validData);

            // ファイル名生成
            $filename = str_replace('{Ymd_His}', date('Ymd_His'), config('zengin.filename_template'));

            // 統計情報を取得
            $stats = $exporter->getStats();

            // ログに記録
            ZenginLog::create([
                'filename' => $filename,
                'total_count' => $stats['total_count'],
                'total_amount' => $stats['total_amount'],
            ]);

            // セッションをクリア
            session()->forget(['zengin_data', 'zengin_filename']);

            // Shift-JISでダウンロード
            return response()->streamDownload(function () use ($content) {
                echo $content;
            }, $filename, [
                'Content-Type' => 'text/plain; charset=shift_jis',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        } catch (\Exception $e) {
            \Log::error('Zengin conversion error: '.$e->getMessage());

            return back()->withErrors([
                'conversion' => '変換処理中にエラーが発生しました: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * 変換後のファイルをダウンロード
     */
    public function download(string $filename): BinaryFileResponse
    {
        // Storage ファサードを使ってファイルの存在チェック
        $filePath = 'zengin/'.$filename;

        if (! Storage::disk('local')->exists($filePath)) {
            abort(404, 'ファイルが見つかりません。');
        }

        // Storage から実際のパスを取得してダウンロード
        $fullPath = Storage::disk('local')->path($filePath);

        // ファイルをダウンロード
        return response()->download($fullPath, $filename, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
