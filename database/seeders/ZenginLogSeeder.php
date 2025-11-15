<?php

namespace Database\Seeders;

use App\Models\ZenginLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ZenginLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ストレージディレクトリの作成
        if (! Storage::disk('local')->exists('zengin')) {
            Storage::disk('local')->makeDirectory('zengin');
        }

        // テストデータ5件を作成
        $testData = [
            [
                'filename' => 'zengin_20251114_100000.txt',
                'file_path' => 'zengin/zengin_20251114_100000.txt',
                'total_count' => 10,
                'total_amount' => 1000000,
                'created_at' => now()->subDays(5),
            ],
            [
                'filename' => 'zengin_20251113_150000.txt',
                'file_path' => 'zengin/zengin_20251113_150000.txt',
                'total_count' => 25,
                'total_amount' => 2500000,
                'created_at' => now()->subDays(4),
            ],
            [
                'filename' => 'zengin_20251112_120000.txt',
                'file_path' => 'zengin/zengin_20251112_120000.txt',
                'total_count' => 15,
                'total_amount' => 1500000,
                'created_at' => now()->subDays(3),
            ],
            [
                'filename' => 'zengin_20251111_140000.txt',
                'file_path' => 'zengin/zengin_20251111_140000.txt',
                'total_count' => 30,
                'total_amount' => 3000000,
                'created_at' => now()->subDays(2),
            ],
            [
                'filename' => 'zengin_20251110_160000.txt',
                'file_path' => 'zengin/zengin_20251110_160000.txt',
                'total_count' => 20,
                'total_amount' => 2000000,
                'created_at' => now()->subDay(),
            ],
        ];

        foreach ($testData as $data) {
            // ダミーファイルの作成
            $dummyContent = $this->createDummyZenginFile($data['total_count'], $data['total_amount']);
            Storage::disk('local')->put($data['file_path'], $dummyContent);

            // DBレコードの作成
            ZenginLog::create($data);
        }

        $this->command->info('✅ ZenginLog テストデータを5件作成しました');
    }

    /**
     * ダミーの全銀フォーマットファイルを生成
     *
     * @param  int  $count  レコード数
     * @param  int  $totalAmount  合計金額
     * @return string
     */
    private function createDummyZenginFile(int $count, int $totalAmount): string
    {
        $lines = [];
        $amountPerRecord = (int) ($totalAmount / $count);

        for ($i = 0; $i < $count; $i++) {
            // 120バイトのダミーレコード（Shift-JIS）
            $line = sprintf(
                '20001%-15s001%-15s    1%07d%-30s%010d%-30s',
                'ﾐｽﾞﾎｷﾞﾝｺｳ',
                'ﾄｳｷｮｳｴｲｷﾞｮｳﾌﾞ',
                1234567 + $i,
                'ﾃｽﾄﾀﾛｳ ' . ($i + 1),
                $amountPerRecord,
                ''
            );

            // UTF-8 → Shift-JIS 変換
            $sjisLine = mb_convert_encoding($line, 'SJIS-win', 'UTF-8');

            // 120バイトに調整
            if (strlen($sjisLine) > 120) {
                $sjisLine = substr($sjisLine, 0, 120);
            } elseif (strlen($sjisLine) < 120) {
                $sjisLine = str_pad($sjisLine, 120, ' ', STR_PAD_RIGHT);
            }

            $lines[] = $sjisLine;
        }

        // CRLF で連結（末尾にも改行）
        return implode("\r\n", $lines) . "\r\n";
    }
}
