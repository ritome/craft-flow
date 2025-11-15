<?php

declare(strict_types=1);

namespace App\Services\Zengin;

use Illuminate\Support\Facades\Storage;

/**
 * 全銀フォーマット変換サービス
 *
 * Excel データから固定長120文字のテキストファイルを生成します。
 */
class ZenginConverter
{
    /**
     * 1レコードの固定長（文字数）
     */
    private const RECORD_LENGTH = 120;

    /**
     * 各フィールドの長さ定義（全銀フォーマット準拠）
     */
    private const FIELD_LENGTHS = [
        'bank_code' => 4,           // 金融機関コード（4桁）
        'bank_name' => 15,          // 金融機関名（15文字）
        'branch_code' => 3,         // 支店コード（3桁）
        'branch_name' => 15,        // 支店名（15文字）
        'account_type' => 1,        // 預金種目（1桁）
        'account_number' => 7,      // 口座番号（7桁）
        'account_holder' => 30,     // 口座名義（30文字）
        'amount' => 10,             // 金額（10桁）
        'customer_name' => 20,      // 顧客名（参考、20文字）
    ];

    /**
     * 複数行のデータを固定長テキストに変換し、ファイルとして保存
     *
     * @param  array  $data  Excel から読み込んだデータの配列
     * @return string 保存したファイル名
     */
    public function convertToFixedLength(array $data): string
    {
        $lines = [];

        // 各行のデータを固定長の行に変換
        foreach ($data as $row) {
            $lines[] = $this->convertRowToFixedLength($row);
        }

        // 全ての行を結合（改行で区切る）
        $content = implode("\n", $lines);

        // ファイルを保存
        $filename = $this->saveToFile($content);

        return $filename;
    }

    /**
     * 1行のデータを固定長120文字の文字列に変換
     *
     * @param  array  $row  1行分のデータ
     *                      [
     *                      'bank_code' => '0005',
     *                      'bank_name' => '三菱UFJ銀行',
     *                      'branch_code' => '001',
     *                      'branch_name' => '新宿支店',
     *                      'account_type' => '普通',
     *                      'account_number' => '1234567',
     *                      'account_holder' => 'ヤマダタロウ',
     *                      'amount' => '100000',
     *                      ]
     * @return string 固定長120文字の文字列
     */
    private function convertRowToFixedLength(array $row): string
    {
        // 各フィールドを固定長に変換（全銀フォーマット形式）
        $bankCode = $this->padRight($row['bank_code'] ?? '', self::FIELD_LENGTHS['bank_code'], '0');
        $bankName = $this->padLeft($row['bank_name'] ?? '', self::FIELD_LENGTHS['bank_name']);
        $branchCode = $this->padRight($row['branch_code'] ?? '', self::FIELD_LENGTHS['branch_code'], '0');
        $branchName = $this->padLeft($row['branch_name'] ?? '', self::FIELD_LENGTHS['branch_name']);
        $accountType = $this->convertAccountType($row['account_type'] ?? '');
        $accountNumber = $this->padRight($row['account_number'] ?? '', self::FIELD_LENGTHS['account_number'], '0');
        $accountHolder = $this->padLeft($row['account_holder'] ?? '', self::FIELD_LENGTHS['account_holder']);
        $amount = $this->padRight($this->cleanAmount($row['amount'] ?? '0'), self::FIELD_LENGTHS['amount'], '0');

        // 各フィールドを連結
        // 合計: 4 + 15 + 3 + 15 + 1 + 7 + 30 + 10 = 85文字
        $line = $bankCode.$bankName.$branchCode.$branchName.$accountType.$accountNumber.$accountHolder.$amount;

        // 残り35文字をスペースで埋めて120文字にする
        $line = $this->padLeft($line, self::RECORD_LENGTH);

        return $line;
    }

    /**
     * 左詰め：文字列を指定された長さに調整（右側をスペースで埋める）
     *
     * 例: padLeft('ABC', 5) => 'ABC  '
     *
     * @param  string  $str  元の文字列
     * @param  int  $length  固定長
     * @param  string  $pad  埋める文字（デフォルト: 半角スペース）
     * @return string 固定長に調整された文字列
     */
    private function padLeft(string $str, int $length, string $pad = ' '): string
    {
        // 文字列が長すぎる場合は切り詰める
        if (mb_strlen($str) > $length) {
            return mb_substr($str, 0, $length);
        }

        // 右側を埋める（左詰め）
        return str_pad($str, $length, $pad, STR_PAD_RIGHT);
    }

    /**
     * 右詰め：文字列を指定された長さに調整（左側を指定文字で埋める）
     *
     * 例: padRight('123', 7, '0') => '0000123'
     *
     * @param  string  $str  元の文字列
     * @param  int  $length  固定長
     * @param  string  $pad  埋める文字（デフォルト: 0）
     * @return string 固定長に調整された文字列
     */
    private function padRight(string $str, int $length, string $pad = '0'): string
    {
        // 文字列が長すぎる場合は切り詰める
        if (mb_strlen($str) > $length) {
            return mb_substr($str, 0, $length);
        }

        // 左側を埋める（右詰め）
        return str_pad($str, $length, $pad, STR_PAD_LEFT);
    }

    /**
     * 預金種目を数値コードに変換
     *
     * - 普通、普通預金 => 1
     * - 当座、当座預金 => 2
     * - それ以外 => 1（デフォルトは普通）
     *
     * @param  string  $accountType  預金種目（日本語）
     * @return string 1文字のコード
     */
    private function convertAccountType(string $accountType): string
    {
        // 全角・半角スペースを削除して判定
        $accountType = trim($accountType);

        return match (true) {
            str_contains($accountType, '普通') => '1',
            str_contains($accountType, '当座') => '2',
            default => '1', // デフォルトは普通預金
        };
    }

    /**
     * 金額から不要な文字を削除して数値のみにする
     *
     * @param  string|int  $amount  金額
     * @return string 数値のみの金額
     */
    private function cleanAmount(string|int $amount): string
    {
        // 数値に変換
        $amount = (string) $amount;

        // カンマや円記号などを削除
        $amount = str_replace([',', '¥', '円', ' ', '　'], '', $amount);

        // 小数点以下を削除（整数部分のみ）
        if (str_contains($amount, '.')) {
            $amount = explode('.', $amount)[0];
        }

        // 数値でない場合は0
        if (! is_numeric($amount)) {
            return '0';
        }

        return $amount;
    }

    /**
     * 変換したテキストをファイルに保存
     *
     * @param  string  $content  変換後のテキスト
     * @return string 保存したファイル名
     */
    private function saveToFile(string $content): string
    {
        // ファイル名を生成（日時 + ランダム文字列）
        $filename = 'zengin_'.date('YmdHis').'_'.uniqid().'.txt';

        // storage/app/zengin ディレクトリに保存
        Storage::disk('local')->put('zengin/'.$filename, $content);

        return $filename;
    }
}
