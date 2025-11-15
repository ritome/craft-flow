<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\ZenginExporter;

echo "\n╔══════════════════════════════════════════╗\n";
echo "║  全角カタカナ→半角カナ 変換テスト      ║\n";
echo "╚══════════════════════════════════════════╝\n\n";

// 実際の銀行提出用データ（全角カタカナで入力）
$testData = [
    [
        'bank_code' => '0001',
        'bank_name' => 'ミズホギンコウ',
        'branch_code' => '001',
        'branch_name' => 'トウキョウエイギョウブ',
        'account_type' => '普通',
        'account_number' => '1234567',
        'account_holder' => 'ヤマダ　タロウ', // 全角カタカナ
        'amount' => '100000',
    ],
    [
        'bank_code' => '0005',
        'bank_name' => 'ミツビシＵＦＪギンコウ',
        'branch_code' => '005',
        'branch_name' => 'シンジュクシテン',
        'account_type' => '当座',
        'account_number' => '7654321',
        'account_holder' => 'スズキ　ハナコ', // 全角カタカナ
        'amount' => '250000',
    ],
];

echo "📊 入力データ（全角カタカナ）:\n";
foreach ($testData as $i => $row) {
    echo sprintf("行%d: %s\n", $i + 1, $row['account_holder']);
}
echo "\n";

try {
    $exporter = new ZenginExporter;
    $content = $exporter->export($testData);
    
    $filename = 'zengin_katakana_' . date('Ymd_His') . '.txt';
    $path = 'zengin/' . $filename;
    Storage::disk('local')->put($path, $content);
    $fullPath = Storage::disk('local')->path($path);
    
    echo "✅ 変換成功！\n";
    echo "保存先: {$fullPath}\n\n";
    
    // SJIS → UTF-8 に変換して表示
    $lines = explode("\r\n", $content);
    $lines = array_filter($lines, fn($line) => $line !== '');
    
    echo "📋 変換後の内容（UTF-8表示）:\n";
    echo str_repeat("─", 60) . "\n";
    foreach ($lines as $i => $line) {
        $utf8Line = mb_convert_encoding($line, 'UTF-8', 'SJIS-win');
        echo sprintf("行%d:\n%s\n\n", $i + 1, $utf8Line);
        
        // 受取人名部分を抽出
        $recipientPart = substr($line, 38, 30);
        $recipientUtf8 = mb_convert_encoding($recipientPart, 'UTF-8', 'SJIS-win');
        echo "  受取人名部分: 「{$recipientUtf8}」\n";
        
        if (preg_match('/[ｦ-ﾟ]+/u', $recipientUtf8)) {
            echo "  ✅ 半角カナ検出\n";
        } else {
            echo "  ❌ 半角カナ未検出\n";
        }
        echo "\n";
    }
    
    // 各行のバイト数チェック
    echo "🔍 バイト数チェック:\n";
    foreach ($lines as $i => $line) {
        echo sprintf("行%d: %d バイト %s\n", 
            $i + 1, 
            strlen($line),
            strlen($line) === 120 ? '✅' : '❌'
        );
    }
    
} catch (\Exception $e) {
    echo "❌ エラー: {$e->getMessage()}\n";
    exit(1);
}

echo "\n✅ テスト完了\n";

