<?php

declare(strict_types=1);

namespace App\Services;

use App\Exports\SalesExport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * 集計結果をExcelファイルとして出力するサービス
 */
class ExcelExporter
{
    /**
     * 集計データをExcelファイルとして出力
     *
     * @param  array  $aggregatedData  集計済みデータ
     * @return string 出力されたファイルパス
     */
    public function export(array $aggregatedData): string
    {
        try {
            // 出力ファイル名を生成（仕様書に準拠）
            // 形式: 売上集計_{営業日}_{タイムスタンプ}.xlsx
            $businessDate = $aggregatedData['business_date'] ?? date('Y-m-d');
            $timestamp = date('YmdHis');
            $filename = "売上集計_{$businessDate}_{$timestamp}.xlsx";
            $storagePath = 'exports/'.$filename;

            // Excelファイルを生成
            Excel::store(
                new SalesExport($aggregatedData),
                $storagePath,
                'local'
            );

            // Storage::disk('local')->path()を使用して正しいフルパスを取得
            // localディスクのrootがstorage/app/privateに設定されているため、これを使用
            $fullPath = Storage::disk('local')->path($storagePath);

            Log::info('Excelファイルを生成しました', [
                'path' => $fullPath,
                'business_date' => $businessDate,
                'total_sales' => $aggregatedData['summary']['total_sales'] ?? 0,
                'file_count' => $aggregatedData['file_count'] ?? 0,
            ]);

            return $fullPath;
        } catch (\Exception $e) {
            Log::error('Excelファイルの生成に失敗しました', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \RuntimeException('Excelファイルの生成に失敗しました: '.$e->getMessage(), 0, $e);
        }
    }
}
