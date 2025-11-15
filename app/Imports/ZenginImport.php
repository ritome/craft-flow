<?php

declare(strict_types=1);

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

/**
 * 全銀フォーマット用 Excel インポートクラス
 *
 * Excel ファイルからデータを読み込んで配列として返します。
 * 日本語の列名をそのまま使用するため、WithHeadingRow は使用しません。
 */
class ZenginImport implements ToArray
{
    /**
     * Excel の各行を配列に変換
     *
     * @param  array  $array  Excel の全データ
     */
    public function array(array $array): void
    {
        // このメソッドは maatwebsite/excel によって呼ばれますが、
        // 今回は import() メソッドの戻り値で配列を取得するので、
        // ここでは何もしません
    }
}
