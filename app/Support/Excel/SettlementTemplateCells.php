<?php

declare(strict_types=1);

namespace App\Support\Excel;

/**
 * 精算書テンプレートのセル座標定義
 *
 * resources/excel/settlement_template.xlsx のセル座標を一元管理
 *
 * ※ テンプレートファイルの実際の構造に合わせて、この定数を調整してください
 */
class SettlementTemplateCells
{
    /**
     * ヘッダー部
     *
     * 行4: D4 = 「精算番号:」ラベル, E4 = 番号本体
     * 行5: D5 = 「発行日:」ラベル,     E5 = 日付本体
     */
    public const SETTLEMENT_NUMBER = 'E4';    // 精算番号の値セル

    public const ISSUE_DATE = 'E5';    // 発行日の値セル

    /**
     * 委託先情報
     *
     * 行10: A10 = 委託先名
     * 行11: A11 = 郵便番号
     * 行12: A12 = 住所
     * 行15: A15 = 「【精算期間】 yyyy年mm月dd日 ～ yyyy年mm月dd日」
     */
    public const BILLING_PERIOD = 'A15'; // 精算(請求)期間

    public const CLIENT_NAME = 'A10'; // 委託先名

    public const CLIENT_POSTAL_CODE = 'A11'; // 委託先郵便番号

    public const CLIENT_ADDRESS = 'A12'; // 委託先住所

    /**
     * お支払金額ボックス（上部右側）
     *
     * 行9 : C9  = 「【お支払金額】」ラベル
     * 行10: C10 = お支払金額の数値
     */
    public const PAYMENT_AMOUNT_LABEL = 'C9';  // 「【お支払金額】」ラベル

    public const PAYMENT_AMOUNT = 'C10'; // お支払金額の値

    /**
     * 商品明細テーブル
     *
     * 行16: A16=商品コード, B16=商品名, C16=単価, D16=販売数, E16=売上金額（ヘッダー）
     * 行17〜: 明細データ
     */
    public const DETAIL_START_ROW = 17;  // 明細1行目の行番号（ヘッダーの次の行）

    public const DETAIL_COL_CODE = 'A'; // 商品コード列

    public const DETAIL_COL_NAME = 'B'; // 商品名列

    public const DETAIL_COL_PRICE = 'C'; // 単価列

    public const DETAIL_COL_QTY = 'D'; // 販売数列

    public const DETAIL_COL_AMOUNT = 'E'; // 売上金額列

    /**
     * 集計行（明細の後、動的位置）
     *
     * このテンプレでは:
     *   明細最終行 = 22 の場合、
     *     行27: A27=「小計」,              C27=小計
     *     行28: A28=「委託販売手数料(20%)」, C28=手数料
     *     行29: A29=「消費税(10%)」,        C29=消費税
     *     行30: A30=「振込手数料」,        C30=振込手数料
     *     行31: A31=「お支払金額」,        C31=お支払金額(最終)
     *
     * → 明細終了行からのオフセットは 5〜9 行
     */
    public const SUBTOTAL_ROW_OFFSET = 5; // 明細終了行 +5 行目 = 小計

    public const COMMISSION_ROW_OFFSET = 6; // 明細終了行 +6 行目 = 委託販売手数料

    public const TAX_ROW_OFFSET = 7; // 明細終了行 +7 行目 = 消費税

    public const TRANSFER_FEE_ROW_OFFSET = 8; // 明細終了行 +8 行目 = 振込手数料

    public const PAYMENT_ROW_OFFSET = 9; // 明細終了行 +9 行目 = お支払金額(最終)

    // 集計金額が入っている列（このテンプレでは C 列）
    public const TOTAL_AMOUNT_COL = 'C';

    /**
     * 振込先情報（お支払金額行のあと）
     *
     * このテンプレでは:
     *   行31: お支払金額 行（上の PAYMENT_ROW_OFFSET で決まる）
     *   行33: A33 = 「【お振込予定日】 yyyy年mm月dd日」
     *   行34: A34 = 「【振込先】」
     *   行35: A35 = 「○○銀行 △△支店(101) 普通 1234567」
     *   行36: A36 = 「口座名義: 〜」
     *
     * bankInfoCells() では、以下の3セルを使う想定：
     *   - payment_date … A33
     *   - bank_label   … A34（「【振込先】」の行）
     *   - bank_info    … A35（銀行名〜口座番号の行）
     */
    public const BANK_INFO_ROW_OFFSET = 2; // お支払金額行 +2 行 = お振込予定日

    public const BANK_NAME_ROW_OFFSET = 3; // お支払金額行 +3 行 = 【振込先】ラベル

    public const ACCOUNT_INFO_ROW_OFFSET = 4; // お支払金額行 +4 行 = 銀行名・支店・口座行

    /**
     * テンプレートファイルパス
     */
    public const TEMPLATE_PATH = 'resources/excel/settlement_template.xlsx';

    /**
     * セル座標を取得（行・列を指定）
     *
     * @param  string  $column  列（A, B, C...）
     * @param  int  $row  行番号
     * @return string セル座標（例：'A15'）
     */
    public static function cell(string $column, int $row): string
    {
        return $column.$row;
    }

    /**
     * 明細行の各列セル座標を取得
     *
     * @param  int  $row  行番号
     * @return array<string, string> ['code' => 'A17', 'name' => 'B17', ...]
     */
    public static function detailCells(int $row): array
    {
        return [
            'code' => self::cell(self::DETAIL_COL_CODE, $row),
            'name' => self::cell(self::DETAIL_COL_NAME, $row),
            'price' => self::cell(self::DETAIL_COL_PRICE, $row),
            'qty' => self::cell(self::DETAIL_COL_QTY, $row),
            'amount' => self::cell(self::DETAIL_COL_AMOUNT, $row),
        ];
    }

    /**
     * 集計行のセル座標を取得
     *
     * @param  int  $detailEndRow  明細の最終行番号
     * @return array<string, string>
     */
    public static function totalCells(int $detailEndRow): array
    {
        return [
            'subtotal' => self::cell(self::TOTAL_AMOUNT_COL, $detailEndRow + self::SUBTOTAL_ROW_OFFSET),
            'commission' => self::cell(self::TOTAL_AMOUNT_COL, $detailEndRow + self::COMMISSION_ROW_OFFSET),
            'tax' => self::cell(self::TOTAL_AMOUNT_COL, $detailEndRow + self::TAX_ROW_OFFSET),
            'transfer_fee' => self::cell(self::TOTAL_AMOUNT_COL, $detailEndRow + self::TRANSFER_FEE_ROW_OFFSET),
            'payment' => self::cell(self::TOTAL_AMOUNT_COL, $detailEndRow + self::PAYMENT_ROW_OFFSET),
        ];
    }

    /**
     * 振込先情報のセル座標を取得
     *
     * @param  int  $paymentRow  お支払金額行の行番号
     * @return array<string, string>
     */
    public static function bankInfoCells(int $paymentRow): array
    {
        return [
            'payment_date' => self::cell('A', $paymentRow + self::BANK_INFO_ROW_OFFSET),
            'bank_label' => self::cell('A', $paymentRow + self::BANK_NAME_ROW_OFFSET),
            'bank_info' => self::cell('A', $paymentRow + self::ACCOUNT_INFO_ROW_OFFSET),
        ];
    }
}
