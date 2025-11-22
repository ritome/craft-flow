<?php

declare(strict_types=1);

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

/**
 * 全銀フォーマット用Excelインポート
 */
class ZenginImport implements ToArray
{
    /**
     * Excelデータを配列として取得
     *
     * @param  array  $array  Excelの全データ
     */
    public function array(array $array): array
    {
        return $array;
    }
}
