<?php

declare(strict_types=1);

namespace App\Support;

/**
 * 全銀フォーマット用テキスト正規化クラス
 *
 * 半角カナ変換、禁止文字置換、制御文字除去などを行います
 */
class TextNormalizer
{
    /**
     * 全角カナを半角カナに変換
     *
     * PHP 8.4.13 の mb_convert_kana に不具合があるため、独自実装を使用
     *
     * @param  string  $str  変換対象文字列
     * @return string 半角カナに変換された文字列
     */
    public static function toHalfWidthKana(string $str): string
    {
        // 全角→半角変換マップ
        $map = [
            'ア' => 'ｱ', 'イ' => 'ｲ', 'ウ' => 'ｳ', 'エ' => 'ｴ', 'オ' => 'ｵ',
            'カ' => 'ｶ', 'キ' => 'ｷ', 'ク' => 'ｸ', 'ケ' => 'ｹ', 'コ' => 'ｺ',
            'サ' => 'ｻ', 'シ' => 'ｼ', 'ス' => 'ｽ', 'セ' => 'ｾ', 'ソ' => 'ｿ',
            'タ' => 'ﾀ', 'チ' => 'ﾁ', 'ツ' => 'ﾂ', 'テ' => 'ﾃ', 'ト' => 'ﾄ',
            'ナ' => 'ﾅ', 'ニ' => 'ﾆ', 'ヌ' => 'ﾇ', 'ネ' => 'ﾈ', 'ノ' => 'ﾉ',
            'ハ' => 'ﾊ', 'ヒ' => 'ﾋ', 'フ' => 'ﾌ', 'ヘ' => 'ﾍ', 'ホ' => 'ﾎ',
            'マ' => 'ﾏ', 'ミ' => 'ﾐ', 'ム' => 'ﾑ', 'メ' => 'ﾒ', 'モ' => 'ﾓ',
            'ヤ' => 'ﾔ', 'ユ' => 'ﾕ', 'ヨ' => 'ﾖ',
            'ラ' => 'ﾗ', 'リ' => 'ﾘ', 'ル' => 'ﾙ', 'レ' => 'ﾚ', 'ロ' => 'ﾛ',
            'ワ' => 'ﾜ', 'ヲ' => 'ｦ', 'ン' => 'ﾝ',
            'ガ' => 'ｶﾞ', 'ギ' => 'ｷﾞ', 'グ' => 'ｸﾞ', 'ゲ' => 'ｹﾞ', 'ゴ' => 'ｺﾞ',
            'ザ' => 'ｻﾞ', 'ジ' => 'ｼﾞ', 'ズ' => 'ｽﾞ', 'ゼ' => 'ｾﾞ', 'ゾ' => 'ｿﾞ',
            'ダ' => 'ﾀﾞ', 'ヂ' => 'ﾁﾞ', 'ヅ' => 'ﾂﾞ', 'デ' => 'ﾃﾞ', 'ド' => 'ﾄﾞ',
            'バ' => 'ﾊﾞ', 'ビ' => 'ﾋﾞ', 'ブ' => 'ﾌﾞ', 'ベ' => 'ﾍﾞ', 'ボ' => 'ﾎﾞ',
            'パ' => 'ﾊﾟ', 'ピ' => 'ﾋﾟ', 'プ' => 'ﾌﾟ', 'ペ' => 'ﾍﾟ', 'ポ' => 'ﾎﾟ',
            'ァ' => 'ｧ', 'ィ' => 'ｨ', 'ゥ' => 'ｩ', 'ェ' => 'ｪ', 'ォ' => 'ｫ',
            'ッ' => 'ｯ', 'ャ' => 'ｬ', 'ュ' => 'ｭ', 'ョ' => 'ｮ',
            'ー' => 'ｰ', '、' => '､', '。' => '｡', '・' => '･',
            '「' => '｢', '」' => '｣', '゛' => 'ﾞ', '゜' => 'ﾟ',
            '　' => ' ', // 全角スペース→半角スペース
        ];
        
        // 全角英数字→半角英数字
        $str = mb_convert_encoding(mb_convert_encoding($str, 'SJIS-win', 'UTF-8'), 'UTF-8', 'SJIS-win');
        $str = str_replace(array_keys($map), array_values($map), $str);
        
        // 全角英数を半角に（0-9, A-Z, a-z）
        $str = mb_convert_kana($str, 'as', 'UTF-8');
        
        return $str;
    }

    /**
     * 禁止文字を許可された文字に置換
     *
     * @param  string  $str  変換対象文字列
     * @return string 置換後の文字列
     */
    public static function replaceProhibited(string $str): string
    {
        $prohibitedMap = config('zengin.prohibited_map', []);

        return str_replace(
            array_keys($prohibitedMap),
            array_values($prohibitedMap),
            $str
        );
    }

    /**
     * 制御文字を除去
     *
     * @param  string  $str  変換対象文字列
     * @return string 制御文字を除去した文字列
     */
    public static function removeControlChars(string $str): string
    {
        // 制御文字（0x00-0x1F、0x7F）を除去、ただしタブ、改行、復帰は残す
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $str);
    }

    /**
     * 全角スペースを半角スペースに変換
     *
     * @param  string  $str  変換対象文字列
     * @return string 変換後の文字列
     */
    public static function normalizeSpaces(string $str): string
    {
        return str_replace('　', ' ', $str);
    }

    /**
     * 全銀フォーマット用に文字列を正規化
     *
     * 以下の処理を順に実行：
     * 1. 禁止文字の置換
     * 2. 全角スペースを半角スペースに
     * 3. 制御文字の除去
     * 4. 半角カナへの変換
     *
     * @param  string  $str  変換対象文字列
     * @return string 正規化された文字列
     */
    public static function sanitizeForZengin(string $str): string
    {
        // 1. 禁止文字の置換
        $str = self::replaceProhibited($str);

        // 2. 全角スペースを半角スペースに
        $str = self::normalizeSpaces($str);

        // 3. 制御文字の除去
        $str = self::removeControlChars($str);

        // 4. 半角カナへの変換
        $str = self::toHalfWidthKana($str);

        return $str;
    }

    /**
     * Shift-JIS で表現できない文字を検出
     *
     * @param  string  $str  検証対象文字列（UTF-8）
     * @return array 表現できない文字の配列
     */
    public static function detectUnsupportedChars(string $str): array
    {
        $sjis = @mb_convert_encoding($str, 'SJIS-win', 'UTF-8');
        $back = mb_convert_encoding($sjis, 'UTF-8', 'SJIS-win');

        if ($str === $back) {
            return [];
        }

        // 1文字ずつチェック
        $unsupported = [];
        $chars = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($chars as $char) {
            $sjisChar = @mb_convert_encoding($char, 'SJIS-win', 'UTF-8');
            $backChar = mb_convert_encoding($sjisChar, 'UTF-8', 'SJIS-win');

            if ($char !== $backChar) {
                $unsupported[] = $char;
            }
        }

        return array_unique($unsupported);
    }
}
