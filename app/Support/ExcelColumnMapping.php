<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Excel列名マッピング定義
 *
 * docs/excel_layout_*.md の仕様に基づき、
 * Excel上の日本語列名とシステム内部の英語カラム名をマッピング
 */
class ExcelColumnMapping
{
    /**
     * 顧客マスタ（委託先マスタ）の列マッピング
     *
     * 参照: docs/excel_layout_clients.md
     */
    public const CUSTOMER_COLUMNS = [
        // Excel列名 => システム内部カラム名
        '委託先ID' => 'client_code',
        'クライアントID' => 'client_code',  // 別表記も許容
        '委託先名' => 'client_name',
        '会社名' => 'client_name',  // 別表記も許容
        '住所' => 'address',
        '郵便番号' => 'postal_code',
        '振込銀行名' => 'bank_name',
        '銀行名' => 'bank_name',
        '振込支店名' => 'branch_name',
        '支店名' => 'branch_name',
        '支店番号' => 'branch_code',
        '口座種別' => 'account_type',
        '口座番号' => 'account_number',
        '口座名義' => 'account_name',
        '手数料率' => 'commission_rate',
    ];

    /**
     * 売上データの列マッピング
     *
     * 参照: docs/excel_layout_sales.md
     */
    public const SALES_COLUMNS = [
        // Excel列名 => システム内部カラム名
        '日付' => 'sale_date',
        '売上日' => 'sale_date',
        'レシート番号' => 'receipt_number',
        '委託先ID' => 'client_code',
        'クライアントID' => 'client_code',
        '委託先名' => 'client_name',
        '会社名' => 'client_name',
        '商品コード' => 'product_code',
        '商品名' => 'product_name',
        '単価' => 'unit_price',
        '販売数量' => 'quantity',
        '販売数' => 'quantity',
        '売上金額' => 'amount',
        'カテゴリ' => 'category',
        '手数料率' => 'commission_rate',
        '備考' => 'note',
    ];

    /**
     * Excel列名を内部カラム名に変換
     *
     * @param  string  $excelColumnName  Excel上の列名
     * @param  array  $mapping  マッピング定義配列
     * @return string 内部カラム名（見つからない場合はそのまま返す）
     */
    public static function toInternalColumn(string $excelColumnName, array $mapping): string
    {
        // 前後の空白を削除
        $trimmed = trim($excelColumnName);

        // マッピングに存在すれば変換
        if (isset($mapping[$trimmed])) {
            return $mapping[$trimmed];
        }

        // 見つからない場合は元の名前をそのまま返す（後続処理でエラーになる）
        return $trimmed;
    }

    /**
     * 顧客マスタの必須列を取得
     *
     * @return array<string>
     */
    public static function getRequiredCustomerColumns(): array
    {
        return [
            'client_code',
            'client_name',
        ];
    }

    /**
     * 売上データの必須列を取得
     *
     * @return array<string>
     */
    public static function getRequiredSalesColumns(): array
    {
        return [
            'sale_date',
            'client_code',
            'product_name',
            'unit_price',
            'quantity',
            'amount',
        ];
    }

    /**
     * 必須列が全て存在するかチェック
     *
     * @param  array<string>  $headers  実際のヘッダー配列
     * @param  array<string>  $required  必須列の配列
     * @return array<string> 不足している列名の配列
     */
    public static function getMissingColumns(array $headers, array $required): array
    {
        return array_diff($required, $headers);
    }
}
