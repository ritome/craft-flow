<?php

declare(strict_types=1);

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

/**
 * 売上データインポート
 *
 * Issue #12: 精算用Excelデータアップロード機能
 *
 * 必要な列：
 * - sale_date: 売上日
 * - client_code: 委託先コード
 * - product_name: 商品名
 * - unit_price: 単価
 * - quantity: 数量
 * - amount: 売上金額
 * - commission_rate: 手数料率
 */
class SalesImport implements ToArray
{
    /**
     * Excel データを配列として取得
     */
    public function array(array $array): array
    {
        return $array;
    }
}
