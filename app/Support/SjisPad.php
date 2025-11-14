<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Shift-JIS バイト長基準のパディングクラス
 *
 * UTF-8文字列をShift-JISに変換し、指定バイト長に整形します
 */
class SjisPad
{
    /**
     * 文字列を指定バイト長に整形（Shift-JIS基準）
     *
     * @param  string  $utf8  UTF-8文字列
     * @param  int  $bytes  目標バイト数
     * @param  string  $pad  パディング文字（デフォルト: 半角スペース）
     * @return string Shift-JISバイト列（バイナリ文字列）
     *
     * @throws \RuntimeException Shift-JIS変換に失敗した場合
     */
    public static function padBytes(string $utf8, int $bytes, string $pad = ' '): string
    {
        // 1. UTF-8文字列を正規化
        $normalized = TextNormalizer::sanitizeForZengin($utf8);

        // 2. Shift-JISに変換
        $sjis = @mb_convert_encoding($normalized, 'SJIS-win', 'UTF-8');

        if ($sjis === false || $sjis === '') {
            // 変換失敗時は空文字列として処理
            $sjis = '';
        }

        // 3. 現在のバイト長を取得
        $currentBytes = strlen($sjis);

        // 4. バイト長が超過している場合は切り詰め
        if ($currentBytes > $bytes) {
            $sjis = self::truncateBytes($sjis, $bytes);
        }

        // 5. バイト長が不足している場合はパディング
        if (strlen($sjis) < $bytes) {
            $sjis = self::padRight($sjis, $bytes, $pad);
        }

        return $sjis;
    }

    /**
     * Shift-JISバイト列を指定バイト数に切り詰め
     *
     * マルチバイト文字の境界を壊さないよう、1バイトずつ削る
     *
     * @param  string  $sjis  Shift-JISバイト列
     * @param  int  $bytes  目標バイト数
     * @return string 切り詰められたShift-JISバイト列
     */
    private static function truncateBytes(string $sjis, int $bytes): string
    {
        // 指定バイト数まで1バイトずつ削っていく
        while (strlen($sjis) > $bytes) {
            // 末尾1バイトを削除
            $sjis = substr($sjis, 0, -1);

            // UTF-8に戻して再度Shift-JISに変換し、破損していないか確認
            $utf8 = @mb_convert_encoding($sjis, 'UTF-8', 'SJIS-win');
            $reconv = @mb_convert_encoding($utf8, 'SJIS-win', 'UTF-8');

            // 破損していなければOK
            if ($reconv === $sjis) {
                continue;
            }

            // 破損している場合はさらに1バイト削る
            if (strlen($sjis) > 0) {
                $sjis = substr($sjis, 0, -1);
            }
        }

        return $sjis;
    }

    /**
     * Shift-JISバイト列の右側にパディング文字を追加
     *
     * @param  string  $sjis  Shift-JISバイト列
     * @param  int  $bytes  目標バイト数
     * @param  string  $pad  パディング文字（UTF-8）
     * @return string パディングされたShift-JISバイト列
     */
    private static function padRight(string $sjis, int $bytes, string $pad): string
    {
        // パディング文字をShift-JISに変換
        $padSjis = mb_convert_encoding($pad, 'SJIS-win', 'UTF-8');
        $padLength = strlen($padSjis);

        // 不足バイト数を計算
        $shortage = $bytes - strlen($sjis);

        // パディング文字を必要な回数分繰り返す
        $paddingCount = (int) ceil($shortage / $padLength);
        $padding = str_repeat($padSjis, $paddingCount);

        // 正確なバイト数に調整
        $padding = substr($padding, 0, $shortage);

        return $sjis.$padding;
    }

    /**
     * 数値を指定バイト数で左ゼロ埋め
     *
     * @param  int|string  $number  数値
     * @param  int  $bytes  目標バイト数
     * @return string ゼロ埋めされた文字列（ASCII）
     */
    public static function padNumber(int|string $number, int $bytes): string
    {
        $str = (string) $number;

        // 数値以外の文字を除去
        $str = preg_replace('/[^0-9]/', '', $str);

        // 左ゼロ埋め
        return str_pad($str, $bytes, '0', STR_PAD_LEFT);
    }

    /**
     * Shift-JISバイト列の実際のバイト長を取得
     *
     * @param  string  $sjis  Shift-JISバイト列
     * @return int バイト長
     */
    public static function getByteLength(string $sjis): int
    {
        return strlen($sjis);
    }

    /**
     * UTF-8文字列をShift-JIS変換した際のバイト長を計算
     *
     * @param  string  $utf8  UTF-8文字列
     * @return int Shift-JIS変換後のバイト長
     */
    public static function calculateSjisLength(string $utf8): int
    {
        $sjis = mb_convert_encoding($utf8, 'SJIS-win', 'UTF-8');

        return strlen($sjis);
    }
}
