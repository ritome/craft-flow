<?php

declare(strict_types=1);

namespace App\Services;

use App\Exports\SalesDataExport;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

/**
 * 集計結果をExcelファイルとして出力するサービス
 */
class ExcelExporter
{
    /**
     * 集計データをExcelファイルとして出力
     *
     * @param array $aggregatedData 集計済みデータ
     * @return string 出力されたファイルパス
     */
    public function export(array $aggregatedData): string
    {
        try {
            // 出力ファイル名を生成
            $filename = 'sales_data_'.date('YmdHis').'.xlsx';
            $storagePath = 'exports/'.$filename;

            // Excelファイルを生成
            Excel::store(
                new SalesDataExport($aggregatedData),
                $storagePath,
                'local'
            );

            $fullPath = storage_path('app/'.$storagePath);

            Log::info('Excelファイルを生成しました', [
                'path' => $fullPath,
                'total_sales' => $aggregatedData['total_sales'] ?? 0,
                'items_count' => count($aggregatedData['items'] ?? []),
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

