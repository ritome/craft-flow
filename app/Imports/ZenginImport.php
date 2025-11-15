<?php

declare(strict_types=1);

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

/**
 * 全銀フォーマット用Excelインポート
 *
 * Excelファイルを配列として読み込む
 */
class ZenginImport implements ToArray
{
    /**
     * Excelデータを配列に変換
     *
     * @param  array  $array  Excelの全データ
     * @return array 変換後のデータ
     */
    public function array(array $array): array
    {
        return $array;
    }
}

