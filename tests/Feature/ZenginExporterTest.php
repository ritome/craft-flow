<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\ZenginExporter;
use Tests\TestCase;

/**
 * 全銀フォーマットエクスポーター機能テスト
 */
class ZenginExporterTest extends TestCase
{
    /**
     * @test
     * 正常な変換が成功すること
     */
    public function successful_conversion(): void
    {
        $exporter = new ZenginExporter;

        $testData = [
            [
                '金融機関コード' => '0001',
                '金融機関名' => 'みずほ銀行',
                '支店コード' => '001',
                '支店名' => '東京営業部',
                '預金種目' => '1',
                '口座番号' => '1234567',
                '口座名義（カナ）' => 'ヤマダタロウ',
                '振込金額' => 100000,
            ],
        ];

        $result = $exporter->export($testData);

        // 結果が文字列であること
        $this->assertIsString($result);

        // 改行で分割
        $lines = explode(config('zengin.newline'), trim($result));

        // 1行であること
        $this->assertCount(1, $lines);

        // 各行が120バイトであること（Shift-JIS変換後）
        foreach ($lines as $line) {
            $sjis = mb_convert_encoding($line, 'SJIS-win', 'UTF-8');
            $this->assertEquals(120, strlen($sjis), "行のバイト長が120バイトではありません: ".strlen($sjis));
        }

        // 統計情報が正しいこと
        $stats = $exporter->getStats();
        $this->assertEquals(1, $stats['total_count']);
        $this->assertEquals(100000, $stats['total_amount']);
        $this->assertEmpty($stats['errors']);
    }

    /**
     * @test
     * 改行コードがCRLFであること
     */
    public function output_has_crlf_newline(): void
    {
        $exporter = new ZenginExporter;

        $testData = [
            [
                '金融機関コード' => '0001',
                '金融機関名' => 'みずほ銀行',
                '支店コード' => '001',
                '支店名' => '東京営業部',
                '預金種目' => '1',
                '口座番号' => '1234567',
                '口座名義（カナ）' => 'ヤマダタロウ',
                '振込金額' => 100000,
            ],
            [
                '金融機関コード' => '0005',
                '金融機関名' => '三菱UFJ銀行',
                '支店コード' => '005',
                '支店名' => '新宿支店',
                '預金種目' => '2',
                '口座番号' => '7654321',
                '口座名義（カナ）' => 'スズキハナコ',
                '振込金額' => 250000,
            ],
        ];

        $result = $exporter->export($testData);

        // CRLF改行が含まれていること
        $this->assertStringContainsString("\r\n", $result);

        // LFのみの改行は含まれないこと（CR+LFの形のみ）
        $withoutCr = str_replace("\r", '', $result);
        $lfOnlyCount = substr_count($withoutCr, "\n");
        $crlfCount = substr_count($result, "\r\n");

        // すべての改行がCRLFであること
        $this->assertEquals($crlfCount, $lfOnlyCount);
    }

    /**
     * @test
     * バリデーションエラーが検出されること
     */
    public function validation_error_is_detected(): void
    {
        $exporter = new ZenginExporter;

        $testData = [
            [
                '金融機関コード' => '001', // 4桁でない
                '金融機関名' => 'みずほ銀行',
                '支店コード' => '001',
                '支店名' => '東京営業部',
                '預金種目' => '1',
                '口座番号' => '1234567',
                '口座名義（カナ）' => 'ヤマダタロウ',
                '振込金額' => 100000,
            ],
        ];

        $this->expectException(\Exception::class);
        $exporter->export($testData);
    }

    /**
     * @test
     * 半角カタカナに変換されること
     */
    public function katakana_is_converted_to_halfwidth(): void
    {
        $exporter = new ZenginExporter;

        $testData = [
            [
                '金融機関コード' => '0001',
                '金融機関名' => 'みずほ銀行',
                '支店コード' => '001',
                '支店名' => '東京営業部',
                '預金種目' => '1',
                '口座番号' => '1234567',
                '口座名義（カナ）' => 'ヤマダタロウ', // 全角カタカナ
                '振込金額' => 100000,
            ],
        ];

        $result = $exporter->export($testData);

        // Shift-JISに変換
        $sjis = mb_convert_encoding($result, 'SJIS-win', 'UTF-8');

        // UTF-8に戻す（確認用）
        $utf8 = mb_convert_encoding($sjis, 'UTF-8', 'SJIS-win');

        // 半角カタカナが含まれていること（ﾔﾏﾀﾞﾀﾛｳ）
        $this->assertStringContainsString('ﾔﾏﾀﾞﾀﾛｳ', $utf8);
    }

    /**
     * @test
     * プレビューが正しく生成されること
     */
    public function preview_is_generated_correctly(): void
    {
        $exporter = new ZenginExporter;

        $testData = [
            [
                '金融機関コード' => '0001',
                '金融機関名' => 'みずほ銀行',
                '支店コード' => '001',
                '支店名' => '東京営業部',
                '預金種目' => '1',
                '口座番号' => '1234567',
                '口座名義（カナ）' => 'ヤマダタロウ',
                '振込金額' => 100000,
            ],
        ];

        $preview = $exporter->preview($testData);

        // 配列が返ること
        $this->assertIsArray($preview);

        // 1件のプレビューデータ
        $this->assertCount(1, $preview);

        // 各行にメタデータが含まれること
        $this->assertArrayHasKey('_line_number', $preview[0]);
        $this->assertArrayHasKey('_has_error', $preview[0]);
        $this->assertArrayHasKey('_error_message', $preview[0]);

        // エラーがないこと
        $this->assertFalse($preview[0]['_has_error']);
    }
}



