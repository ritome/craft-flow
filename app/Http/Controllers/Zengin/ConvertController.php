<?php

declare(strict_types=1);

namespace App\Http\Controllers\Zengin;

use App\Http\Controllers\Controller;
use App\Imports\ZenginImport;
use App\Services\Zengin\ZenginConverter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        return view('zengin.upload');
    }

    /**
     * Excel ファイルを受け取って変換処理を実行
     */
    public function convert(Request $request, ZenginConverter $converter): View|RedirectResponse
    {
        // バリデーション: ファイルが必須で、拡張子と容量をチェック
        $validated = $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB以下
        ], [
            'excel_file.required' => 'ファイルを選択してください。',
            'excel_file.file' => 'ファイルをアップロードしてください。',
            'excel_file.mimes' => 'Excel ファイル（xlsx, xls, csv）をアップロードしてください。',
            'excel_file.max' => 'ファイルサイズは10MB以下にしてください。',
        ]);

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

            // デバッグ: 読み込んだデータを確認（一時的）
            \Log::info('Total rows: '.count($allRows));
            \Log::info('Headers: '.json_encode($headers, JSON_UNESCAPED_UNICODE));
            if (! empty($allRows)) {
                \Log::info('First data row: '.json_encode($allRows[0], JSON_UNESCAPED_UNICODE));
            }

            // 列名とデータを組み合わせて連想配列に変換
            $rows = [];
            foreach ($allRows as $row) {
                $associativeRow = [];
                foreach ($headers as $index => $header) {
                    $associativeRow[$header] = $row[$index] ?? null;
                }
                $rows[] = $associativeRow;
            }

            \Log::info('First associative row: '.json_encode($rows[0] ?? [], JSON_UNESCAPED_UNICODE));
        } catch (\Exception $e) {
            \Log::error('Excel import error: '.$e->getMessage());

            return back()->withErrors([
                'excel_file' => 'Excel ファイルの読み込みに失敗しました。ファイル形式を確認してください。',
            ])->withInput();
        }

        // データを整形（新しい列構成に対応）
        $formattedData = [];
        foreach ($rows as $row) {
            // 必須項目のチェック（空の行をスキップ）
            if (empty($row['金融機関名']) && empty($row['口座番号'])) {
                continue;
            }

            $formattedData[] = [
                // 金融機関情報
                'bank_code' => $row['金融機関コード'] ?? '',
                'bank_name' => $row['金融機関名'] ?? '',
                'branch_code' => $row['支店コード'] ?? '',
                'branch_name' => $row['支店名'] ?? '',

                // 口座情報
                'account_type' => $row['預金種目'] ?? '',
                'account_number' => $row['口座番号'] ?? '',
                'account_holder' => $row['口座名義カナ'] ?? $row['口座名義（カナ）'] ?? '',

                // 金額
                'amount' => $row['振込金額'] ?? '0',

                // その他（参考情報）
                'customer_name' => $row['事業者名'] ?? '',
                'transfer_date' => $row['振込予定日'] ?? '',
            ];
        }

        // デバッグ: 整形後のデータを確認（一時的）
        \Log::info('Formatted data count: '.count($formattedData));
        if (! empty($formattedData)) {
            \Log::info('First formatted data: '.json_encode($formattedData[0], JSON_UNESCAPED_UNICODE));
        }

        // データが空の場合はエラー
        if (empty($formattedData)) {
            return back()->withErrors([
                'excel_file' => 'Excel ファイルにデータが見つかりませんでした。ヘッダー行と最低1行のデータが必要です。',
            ]);
        }

        try {
            // サービスクラスを使って固定長テキストに変換
            $filename = $converter->convertToFixedLength($formattedData);

            return view('zengin.upload', [
                'message' => '変換に成功しました！以下のリンクからダウンロードできます。',
                'filename' => $file->getClientOriginalName(),
                'downloadFilename' => $filename,
                'recordCount' => count($formattedData),
            ]);
        } catch (\Exception $e) {
            \Log::error('Conversion error: '.$e->getMessage());

            return back()->withErrors([
                'excel_file' => '変換処理中にエラーが発生しました。データの形式を確認してください。',
            ])->withInput();
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
