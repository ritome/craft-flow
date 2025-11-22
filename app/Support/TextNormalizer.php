<?php

declare(strict_types=1);

namespace App\Support;

/**
 * 全銀フォーマット用テキスト正規化クラス
 *
 * 受取人名などのテキストを全銀フォーマット仕様に変換します
 */
class TextNormalizer
{
    /**
     * 半角カタカナへ変換
     *
     * @param  string  $str  変換対象文字列
     * @return string 半角カタカナ変換後の文字列
     */
    public static function toHalfWidthKana(string $str): string
    {
        // 全角カタカナ → 半角カタカナ の変換マップ
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
            'ー' => 'ｰ', '・' => '･', '「' => '｢', '」' => '｣', '゛' => 'ﾞ', '゜' => 'ﾟ',
        ];

        // ひらがなをカタカナに変換
        $str = mb_convert_kana($str, 'C', 'UTF-8');

        // カスタムマップで全角カタカナ→半角カタカナ
        $str = str_replace(array_keys($map), array_values($map), $str);

        // 英数字・スペースを半角化
        $str = mb_convert_kana($str, 'as', 'UTF-8');

        return $str;
    }

    /**
     * 禁止文字を置換
     *
     * @param  string  $str  変換対象文字列
     * @return string 禁止文字置換後の文字列
     */
    public static function replaceProhibited(string $str): string
    {
        $map = config('zengin.prohibited_map', []);

        return str_replace(array_keys($map), array_values($map), $str);
    }

    /**
     * 全銀フォーマット用に文字列を整形
     *
     * 禁止文字置換 → 半角カタカナ変換 → 制御文字除去
     *
     * @param  string  $str  変換対象文字列
     * @return string 整形後の文字列
     */
    public static function sanitizeForZengin(string $str): string
    {
        // 禁止文字を置換
        $str = self::replaceProhibited($str);

        // 半角カタカナへ変換
        $str = self::toHalfWidthKana($str);

        // 制御文字を除去
        $str = preg_replace('/[\x00-\x1F\x7F]/u', '', $str);

        // 全角スペースを半角スペースに
        $str = str_replace('　', ' ', $str);

        return $str;
    }
}



