<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Shift-JISバイト長調整ユーティリティ
 *
 * 全銀フォーマット用のSJISバイト長ベースのパディング/切り詰めを提供
 */
class SjisPad
{
    /**
     * 指定バイト数に文字列を調整（右側スペースパディング）
     *
     * @param  string  $utf8  UTF-8文字列
     * @param  int  $bytes  目標バイト数
     * @param  string  $pad  パディング文字（デフォルト: 半角スペース）
     * @return string Shift-JISバイナリ文字列
     */
    public static function padBytes(string $utf8, int $bytes, string $pad = ' '): string
    {
        // 1. UTF-8文字列を正規化（半角カナ化含む）
        $normalized = TextNormalizer::sanitizeForZengin($utf8);

        // 2. Shift-JISに変換
        $sjis = @mb_convert_encoding($normalized, 'SJIS-win', 'UTF-8');

        if ($sjis === false || $sjis === '') {
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
     * 数値を左ゼロ埋めで指定バイト数に調整
     *
     * @param  int|string  $number  数値
     * @param  int  $bytes  目標バイト数
     * @return string 左ゼロ埋めされた文字列
     */
    public static function padNumber(int|string $number, int $bytes): string
    {
        return str_pad((string) $number, $bytes, '0', STR_PAD_LEFT);
    }

    /**
     * Shift-JISバイト列を指定バイト数で安全に切り詰め
     *
     * マルチバイト文字の境界を壊さないように1バイトずつ削除
     *
     * @param  string  $sjis  Shift-JISバイナリ文字列
     * @param  int  $maxBytes  最大バイト数
     * @return string 切り詰められたShift-JISバイナリ文字列
     */
    private static function truncateBytes(string $sjis, int $maxBytes): string
    {
        if (strlen($sjis) <= $maxBytes) {
            return $sjis;
        }

        // 末尾から1バイトずつ削除し、UTF-8に正常に変換できるか確認
        for ($i = $maxBytes; $i > 0; $i--) {
            $truncated = substr($sjis, 0, $i);

            // UTF-8に変換できるか確認（マルチバイト境界チェック）
            $utf8 = @mb_convert_encoding($truncated, 'UTF-8', 'SJIS-win');

            if ($utf8 !== false && $utf8 !== '') {
                return $truncated;
            }
        }

        return '';
    }

    /**
     * Shift-JISバイト列を右側にパディング
     *
     * @param  string  $sjis  Shift-JISバイナリ文字列
     * @param  int  $bytes  目標バイト数
     * @param  string  $pad  パディング文字（半角）
     * @return string パディングされたShift-JISバイナリ文字列
     */
    private static function padRight(string $sjis, int $bytes, string $pad): string
    {
        $currentBytes = strlen($sjis);
        $paddingBytes = $bytes - $currentBytes;

        if ($paddingBytes <= 0) {
            return $sjis;
        }

        // パディング文字を必要な数だけ追加
        return $sjis.str_repeat($pad, $paddingBytes);
    }
}

