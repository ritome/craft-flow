<?php

/**
 * 全銀フォーマット出力テストスクリプト
 * 
 * 実行方法: php storage/samples/test_zengin.php
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\ZenginExporter;

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  全銀フォーマット出力テスト\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// テストデータ（機種依存文字・全角カナを含む）
$testData = [
    [
        'bank_code' => '0001',
        'bank_name' => '㈱ミズホ銀行', // 機種依存文字
        'branch_code' => '001',
        'branch_name' => 'トウキョウ営業部',
        'account_type' => '普通',
        'account_number' => '1234567',
        'account_holder' => '髙橋　太郎', // 異体字
        'amount' => '100000',
    ],
    [
        'bank_code' => '0005',
        'bank_name' => '三菱ＵＦＪ銀行', // 全角英数
        'branch_code' => '005',
        'branch_name' => 'シンジュク①支店', // 丸囲み数字
        'account_type' => '当座',
        'account_number' => '7654321',
        'account_holder' => 'スズキ　ハナコ',
        'amount' => '250000',
    ],
    [
        'bank_code' => '0009',
        'bank_name' => 'りそな銀行',
        'branch_code' => '123',
        'branch_name' => '大阪支店',
        'account_type' => '普通',
        'account_number' => '9876543',
        'account_holder' => 'サトウ　ジロウ',
        'amount' => '500000',
    ],
];

echo "📊 入力データ（" . count($testData) . "件）:\n";
echo "─────────────────────────────────────────\n";
foreach ($testData as $i => $row) {
    echo sprintf("行%d: %s %s → %s（%s円）\n",
        $i + 1,
        $row['bank_name'],
        $row['branch_name'],
        $row['account_holder'],
        number_format((int)$row['amount'])
    );
}
echo "\n";

// エクスポータ実行
try {
    $exporter = new ZenginExporter;
    
    echo "🔄 変換処理中...\n\n";
    $content = $exporter->export($testData);
    
    $stats = $exporter->getStats();
    
    echo "✅ 変換成功！\n\n";
    
    // ファイル保存
    $filename = 'zengin_' . date('Ymd_His') . '.txt';
    $path = 'zengin/' . $filename;
    Storage::disk('local')->put($path, $content);
    
    $fullPath = Storage::disk('local')->path($path);
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "  📄 生成ファイル情報\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "ファイル名: {$filename}\n";
    echo "保存先: {$fullPath}\n";
    echo "ファイルサイズ: " . strlen($content) . " バイト\n";
    echo "総レコード数: {$stats['total_count']} 件\n";
    echo "合計金額: " . number_format($stats['total_amount']) . " 円\n";
    echo "\n";
    
    // バイト数チェック
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "  🔍 各行のバイト数チェック結果\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $lines = explode("\r\n", $content);
    $lines = array_filter($lines, fn($line) => $line !== '');
    
    foreach ($lines as $i => $line) {
        $byteLength = strlen($line);
        $status = $byteLength === 120 ? '✅' : '❌';
        echo sprintf("%s 行%d: %d バイト\n", $status, $i + 1, $byteLength);
    }
    echo "\n";
    
    // 文字コード検証
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "  📝 文字コード・改行コード検証\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $encoding = mb_detect_encoding($content, ['SJIS-win', 'UTF-8', 'EUC-JP'], true);
    $hasCrlf = str_contains($content, "\r\n");
    $hasOnlyLf = !str_contains($content, "\r\n") && str_contains($content, "\n");
    
    echo "文字コード: " . ($encoding === 'SJIS-win' ? '✅ Shift-JIS (SJIS-win)' : "❌ {$encoding}") . "\n";
    echo "改行コード: " . ($hasCrlf ? '✅ CRLF (\\r\\n)' : ($hasOnlyLf ? '❌ LF (\\n)' : '❓ 不明')) . "\n";
    echo "\n";
    
    // 最初の3行をUTF-8に変換して表示
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "  📋 変換済みSJISファイル例（最初の3行）\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $displayLines = array_slice($lines, 0, 3);
    foreach ($displayLines as $i => $line) {
        $utf8Line = mb_convert_encoding($line, 'UTF-8', 'SJIS-win');
        echo sprintf("行%d (120バイト):\n", $i + 1);
        echo $utf8Line . "\n\n";
    }
    
    // コマンド使用例
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "  🔧 検証コマンドの使用例\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "Sailを使用する場合:\n";
    echo "  ./vendor/bin/sail artisan zengin:check {$fullPath}\n\n";
    
    echo "直接PHPを使用する場合:\n";
    echo "  php artisan zengin:check {$fullPath}\n\n";
    
} catch (\Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
    echo "\nスタックトレース:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  ✅ テスト完了\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

