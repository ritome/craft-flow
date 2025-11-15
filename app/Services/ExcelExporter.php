<?php

declare(strict_types=1);

namespace App\Services;

/**
 * 集計結果をExcelファイルとして出力するサービス
 */
class ExcelExporter
{
    /**
     * 集計データをExcelファイルとして出力
     *
     * @param array $aggregatedData 集計済みデータ
     * @param string $outputPath 出力先パス
     * @return string 出力されたファイルパス
     */
    public function export(array $aggregatedData, string $outputPath): string
    {
        // TODO: 実装
        return '';
    }
}

