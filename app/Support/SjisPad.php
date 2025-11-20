<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Shift-JISバイト長調整クラス
 *
 * 全銀フォーマットはShift-JISエンコーディングでバイト長が固定なため、
 * UTF-8文字列をShift-JISに変換後、指定バイト数にパディング/切り詰めを行います
 */
class SjisPad
{
    /**
     * Shift-JISバイト長でパディング/切り詰め
     *
     * @param  string  $utf8  UTF-8文字列
     * @param  int  $bytes  目標バイト数
     * @param  string  $pad  パディング文字（半角スペース）
     * @return string Shift-JISバイト列（そのまま出力可能）
     */
    public static function padBytes(string $utf8, int $bytes, string $pad = ' '): string
    {
        // テキストを正規化
        $normalized = TextNormalizer::sanitizeForZengin($utf8);

        // UTF-8 → Shift-JIS に変換
        $sjis = mb_convert_encoding($normalized, 'SJIS-win', 'UTF-8');

        $currentLength = strlen($sjis);

        // 既に目標バイト数を超えている場合は切り詰め
        if ($currentLength > $bytes) {
            // マルチバイト文字の境界を壊さないよう、1バイトずつ削る
            while (strlen($sjis) > $bytes) {
                $sjis = substr($sjis, 0, -1);
            }

            return $sjis;
        }

        // バイト数が足りない場合はパディング
        if ($currentLength < $bytes) {
            $paddingLength = $bytes - $currentLength;
            $sjis .= str_repeat($pad, $paddingLength);
        }

        return $sjis;
    }

    /**
     * 数値を左ゼロ埋めでパディング
     *
     * @param  int|string  $number  数値
     * @param  int  $length  桁数
     * @return string ゼロ埋め済み文字列
     */
    public static function padNumber(int|string $number, int $length): string
    {
        return str_pad((string) $number, $length, '0', STR_PAD_LEFT);
    }
}



