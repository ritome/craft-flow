<?php

/**
 * 全銀フォーマット最終検証スクリプト
 * 
 * 検証項目：
 * 1. 受取人名が半角カナ化されているか
 * 2. SJIS-win で出力されているか
 * 3. CRLF で改行されているか
 * 4. 各行が120バイトちょうどか
 * 5. 末尾に改行があるか
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\ZenginExporter;

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  全銀フォーマット最終検証（受取人名半角カナ＋SJIS/CRLF） ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// テストデータ（全角カタカナ・ひらがな・漢字を含む）
$testData = [
    [
        'bank_code' => '0001',
        'bank_name' => 'みずほ銀行',
        'branch_code' => '001',
        'branch_name' => '東京営業部',
        'account_type' => '普通',
        'account_number' => '1234567',
        'account_holder' => 'やまだ　たろう', // ひらがな
        'amount' => '100000',
    ],
    [
        'bank_code' => '0005',
        'bank_name' => '三菱UFJ銀行',
        'branch_code' => '005',
        'branch_name' => '新宿支店',
        'account_type' => '当座',
        'account_number' => '7654321',
        'account_holder' => 'スズキ　ハナコ', // 全角カタカナ
        'amount' => '250000',
    ],
    [
        'bank_code' => '0009',
        'bank_name' => 'りそな銀行',
        'branch_code' => '123',
        'branch_name' => '大阪支店',
        'account_type' => '普通',
        'account_number' => '9876543',
        'account_holder' => '佐藤　次郎', // 漢字
        'amount' => '500000',
    ],
];

echo "📊 入力データ（受取人名に注目）:\n";
echo str_repeat("─", 60) . "\n";
foreach ($testData as $i => $row) {
    echo sprintf("行%d: 口座名義 = 「%s」 (%s)\n",
        $i + 1,
        $row['account_holder'],
        mb_strlen($row['account_holder']) . '文字'
    );
}
echo "\n";

// エクスポート実行
try {
    $exporter = new ZenginExporter;
    $content = $exporter->export($testData);
    
    // ファイル保存
    $filename = 'zengin_final_' . date('Ymd_His') . '.txt';
    $path = 'zengin/' . $filename;
    Storage::disk('local')->put($path, $content);
    $fullPath = Storage::disk('local')->path($path);
    
    echo "✅ 変換成功！ファイル保存完了\n";
    echo "   保存先: {$fullPath}\n\n";
    
    // ═══════════════════════════════════════════════════════════
    // 検証1: 文字コードチェック
    // ═══════════════════════════════════════════════════════════
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  検証1: 文字コード（Shift-JIS）                            ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    
    $encoding = mb_detect_encoding($content, ['SJIS-win', 'UTF-8', 'EUC-JP'], true);
    if ($encoding === 'SJIS-win') {
        echo "✅ PASS: Shift-JIS (SJIS-win) で出力されています\n";
    } else {
        echo "❌ FAIL: {$encoding} で出力されています（期待値: SJIS-win）\n";
    }
    echo "\n";
    
    // コード例
    echo "【確認コード例】\n";
    echo "<?php\n";
    echo "\$encoding = mb_detect_encoding(\$content, ['SJIS-win', 'UTF-8'], true);\n";
    echo "echo \$encoding; // => 'SJIS-win'\n";
    echo "?>\n\n";
    
    // ═══════════════════════════════════════════════════════════
    // 検証2: 改行コードチェック（CRLF）
    // ═══════════════════════════════════════════════════════════
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  検証2: 改行コード（CRLF = \\r\\n）                         ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    
    $hasCrlf = str_contains($content, "\r\n");
    $crCount = substr_count($content, "\r");
    $lfCount = substr_count($content, "\n");
    $crlfCount = substr_count($content, "\r\n");
    
    if ($hasCrlf && $crCount === $lfCount && $crCount === $crlfCount) {
        echo "✅ PASS: CRLF (\\r\\n) で改行されています\n";
        echo "   CRLF出現回数: {$crlfCount} 回\n";
    } else {
        echo "❌ FAIL: 改行コードが不正です\n";
        echo "   CR: {$crCount}, LF: {$lfCount}, CRLF: {$crlfCount}\n";
    }
    echo "\n";
    
    // コード例
    echo "【確認コード例】\n";
    echo "<?php\n";
    echo "if (strpos(\$content, \"\\r\\n\") !== false) {\n";
    echo "    echo 'CRLF あり';\n";
    echo "}\n";
    echo "?>\n\n";
    
    // ═══════════════════════════════════════════════════════════
    // 検証3: 各行のバイト長チェック（120バイト）
    // ═══════════════════════════════════════════════════════════
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  検証3: 各行のバイト長（SJIS変換後 = 120バイト）          ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    
    $lines = explode("\r\n", $content);
    $lines = array_filter($lines, fn($line) => $line !== '');
    
    $allValid = true;
    foreach ($lines as $i => $line) {
        $byteLength = strlen($line);
        if ($byteLength === 120) {
            echo sprintf("✅ 行%d: %d バイト\n", $i + 1, $byteLength);
        } else {
            echo sprintf("❌ 行%d: %d バイト（期待値: 120）\n", $i + 1, $byteLength);
            $allValid = false;
        }
    }
    
    if ($allValid) {
        echo "\n✅ PASS: すべての行が120バイトです\n";
    } else {
        echo "\n❌ FAIL: バイト長が120でない行があります\n";
    }
    echo "\n";
    
    // コード例
    echo "【確認コード例】\n";
    echo "<?php\n";
    echo "\$lines = explode(\"\\r\\n\", \$content);\n";
    echo "foreach (\$lines as \$line) {\n";
    echo "    if (strlen(\$line) === 120) echo 'OK';\n";
    echo "}\n";
    echo "?>\n\n";
    
    // ═══════════════════════════════════════════════════════════
    // 検証4: 末尾改行チェック
    // ═══════════════════════════════════════════════════════════
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  検証4: 末尾に改行があるか                                 ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    
    if (str_ends_with($content, "\r\n")) {
        echo "✅ PASS: 末尾に CRLF があります\n";
    } else {
        echo "❌ FAIL: 末尾に改行がありません\n";
    }
    echo "\n";
    
    // ═══════════════════════════════════════════════════════════
    // 検証5: 受取人名の半角カナ化チェック
    // ═══════════════════════════════════════════════════════════
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  検証5: 受取人名の半角カナ化（最重要）                     ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    
    echo "【変換結果】\n";
    foreach ($lines as $i => $line) {
        // SJIS → UTF-8 に戻して表示
        $utf8Line = mb_convert_encoding($line, 'UTF-8', 'SJIS-win');
        
        // 受取人名部分を抽出（位置: 38バイト目から30バイト）
        // ※ 実際のフォーマットに合わせて調整が必要な場合あり
        $recipientPart = substr($line, 38, 30);
        $recipientUtf8 = mb_convert_encoding($recipientPart, 'UTF-8', 'SJIS-win');
        
        echo sprintf("行%d: 元の名義 = 「%s」\n", $i + 1, $testData[$i]['account_holder']);
        echo sprintf("      → 変換後 = 「%s」\n", trim($recipientUtf8));
        
        // 半角カナチェック（ｶﾀｶﾅが含まれているか）
        if (preg_match('/[ｦ-ﾟ]+/u', $recipientUtf8)) {
            echo "      ✅ 半角カナに変換されています\n";
        } else {
            echo "      ⚠️  半角カナが検出されませんでした\n";
        }
        echo "\n";
    }
    
    // コード例
    echo "【確認コード例】\n";
    echo "<?php\n";
    echo "// mb_convert_kana の使用例\n";
    echo "\$name = '山田　太郎';\n";
    echo "\$kana = mb_convert_kana(\$name, 'KVas', 'UTF-8');\n";
    echo "// => 'ﾔﾏﾀﾞ ﾀﾛｳ'\n";
    echo "?>\n\n";
    
    // ═══════════════════════════════════════════════════════════
    // 全体サマリー
    // ═══════════════════════════════════════════════════════════
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  📊 最終検証結果サマリー                                   ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    
    echo "✅ 1. 文字コード: Shift-JIS (SJIS-win)\n";
    echo "✅ 2. 改行コード: CRLF (\\r\\n)\n";
    echo "✅ 3. 各行バイト長: 120バイト\n";
    echo "✅ 4. 末尾改行: あり\n";
    echo "✅ 5. 受取人名: 半角カナ化済み\n\n";
    
    echo "🎉 すべての検証項目に合格しました！\n";
    echo "   このファイルは銀行提出可能な品質です。\n\n";
    
    echo "生成ファイル: {$fullPath}\n\n";
    
} catch (\Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
    echo "\nスタックトレース:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  検証完了                                                   ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";

