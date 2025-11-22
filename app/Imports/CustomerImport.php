<?php

declare(strict_types=1);

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

/**
 * 顧客マスタインポート
 * 
 * Issue #12: 精算用Excelデータアップロード機能
 * 
 * 必要な列：
 * - client_code: 委託先コード
 * - client_name: 委託先名
 * - postal_code: 郵便番号
 * - address: 住所
 * - bank_name: 銀行名
 * - branch_name: 支店名
 * - account_type: 口座種別
 * - account_number: 口座番号
 * - account_name: 口座名義
 */
class CustomerImport implements ToArray
{
    /**
     * Excel データを配列として取得
     *
     * @param  array  $array
     * @return array
     */
    public function array(array $array): array
    {
        return $array;
    }
}



