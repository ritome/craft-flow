<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckZengin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zengin:check {file : 検証する全銀フォーマットファイルのパス}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '全銀フォーマットファイルの形式を検証します（120バイト、Shift-JIS、CRLF）';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file');

        // ファイルの存在確認
        if (! file_exists($filePath)) {
            $this->error('❌ ファイルが見つかりません: '.$filePath);

            return Command::FAILURE;
        }

        $this->info('📄 検証開始: '.$filePath);
        $this->newLine();

        // ファイルの内容を読み込み
        $content = file_get_contents($filePath);

        if ($content === false) {
            $this->error('❌ ファイルの読み込みに失敗しました');

            return Command::FAILURE;
        }

        // Shift-JIS エンコーディングの検証
        $this->info('🔍 文字エンコーディングをチェック中...');
        $encoding = mb_detect_encoding($content, ['SJIS-win', 'UTF-8', 'EUC-JP'], true);

        if ($encoding === 'SJIS-win') {
            $this->info('✅ Shift-JIS (SJIS-win) です');
        } else {
            $this->warn('⚠️  Shift-JIS ではありません（検出: '.($encoding ?: '不明').'）');
        }

        $this->newLine();

        // 改行コードの検証
        $this->info('🔍 改行コードをチェック中...');
        $hasCrlf = str_contains($content, "\r\n");
        $hasLf = str_contains($content, "\n");
        $hasCr = str_contains($content, "\r");

        if ($hasCrlf && ! str_contains(str_replace("\r\n", '', $content), "\n")) {
            $this->info('✅ CRLF (\\r\\n) です');
        } elseif ($hasLf && ! $hasCr) {
            $this->warn('⚠️  LF (\\n) です（期待値: CRLF）');
        } elseif ($hasCr && ! $hasLf) {
            $this->warn('⚠️  CR (\\r) です（期待値: CRLF）');
        } else {
            $this->warn('⚠️  改行コードが混在しています');
        }

        $this->newLine();

        // 各行のバイト長チェック
        $this->info('🔍 各行のバイト長をチェック中...');
        $lines = explode("\n", str_replace("\r\n", "\n", $content));
        $lineCount = 0;
        $errorCount = 0;
        $expectedLength = config('zengin.line_length', 120);

        foreach ($lines as $index => $line) {
            // 末尾の改行を除去
            $line = rtrim($line, "\r\n");

            if ($line === '') {
                continue; // 空行はスキップ
            }

            $lineCount++;
            $lineNumber = $index + 1;
            $byteLength = strlen($line);

            if ($byteLength !== $expectedLength) {
                $this->error(sprintf(
                    '❌ 行 %d: %d バイト（期待値: %d バイト）',
                    $lineNumber,
                    $byteLength,
                    $expectedLength
                ));
                $errorCount++;
            } elseif ($lineCount <= 5) {
                // 最初の5行はOKメッセージを表示
                $this->info(sprintf('✅ 行 %d: %d バイト - OK', $lineNumber, $byteLength));
            }
        }

        $this->newLine();

        // サマリー
        $this->info('📊 検証結果サマリー');
        $this->table(
            ['項目', '値'],
            [
                ['総行数', $lineCount],
                ['エラー行数', $errorCount],
                ['エンコーディング', $encoding ?: '不明'],
                ['改行コード', $hasCrlf ? 'CRLF' : ($hasLf ? 'LF' : 'CR')],
            ]
        );

        $this->newLine();

        if ($errorCount === 0 && $encoding === 'SJIS-win' && $hasCrlf) {
            $this->info('✅ すべての検証に合格しました！');

            return Command::SUCCESS;
        } else {
            $this->warn('⚠️  一部の検証に失敗しました');

            return Command::FAILURE;
        }
    }
}
