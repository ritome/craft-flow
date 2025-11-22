<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SettlementService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;

/**
 * 精算書生成テストコマンド
 *
 * Issue #12: Excelアップロード機能のテスト
 */
class TestSettlementGeneration extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'settlement:test
                            {--customer-file=storage/samples/customer_master.csv : 顧客マスタファイルパス}
                            {--sales-file=storage/samples/sales_data.csv : 売上データファイルパス}
                            {--start-date=2024-11-01 : 請求開始日}
                            {--end-date=2024-11-30 : 請求終了日}';

    /**
     * The console command description.
     */
    protected $description = '精算書生成機能をテストします（Issue #12デバッグ用）';

    /**
     * Execute the console command.
     */
    public function handle(SettlementService $settlementService): int
    {
        $this->info('=== 精算書生成テスト開始 ===');
        $this->newLine();

        // ファイルパス取得
        $customerFilePath = base_path($this->option('customer-file'));
        $salesFilePath = base_path($this->option('sales-file'));

        // ファイル存在確認
        $this->info('1. ファイル存在確認');
        if (! file_exists($customerFilePath)) {
            $this->error("顧客マスタファイルが見つかりません: {$customerFilePath}");

            return Command::FAILURE;
        }
        $this->line("  ✅ 顧客マスタ: {$customerFilePath}");

        if (! file_exists($salesFilePath)) {
            $this->error("売上データファイルが見つかりません: {$salesFilePath}");

            return Command::FAILURE;
        }
        $this->line("  ✅ 売上データ: {$salesFilePath}");
        $this->newLine();

        // UploadedFile オブジェクトを作成
        $this->info('2. ファイルオブジェクト作成');
        try {
            $customerFile = new UploadedFile(
                $customerFilePath,
                'customer_master.csv',
                'text/csv',
                null,
                true // test mode
            );
            $this->line('  ✅ 顧客マスタファイルオブジェクト作成完了');

            $salesFile = new UploadedFile(
                $salesFilePath,
                'sales_data.csv',
                'text/csv',
                null,
                true // test mode
            );
            $this->line('  ✅ 売上データファイルオブジェクト作成完了');
        } catch (\Exception $e) {
            $this->error('ファイルオブジェクト作成エラー: '.$e->getMessage());

            return Command::FAILURE;
        }
        $this->newLine();

        // 精算書生成
        $this->info('3. 精算書生成処理');
        $startDate = $this->option('start-date');
        $endDate = $this->option('end-date');
        $this->line("  請求期間: {$startDate} 〜 {$endDate}");

        try {
            $settlement = $settlementService->generateSettlements(
                billingStartDate: $startDate,
                billingEndDate: $endDate,
                customerFile: $customerFile,
                salesFile: $salesFile
            );

            $this->newLine();
            $this->info('=== 精算書生成成功！ ===');
            $this->newLine();
            $this->line("📋 精算ID: {$settlement->id}");
            $this->line("📅 請求期間: {$settlement->billing_period}");
            $this->line("🏢 委託先数: {$settlement->client_count}件");
            $this->line('💰 総売上金額: ¥'.number_format((float) $settlement->total_sales_amount));
            $this->line('💳 総手数料: ¥'.number_format((float) $settlement->total_commission));
            $this->line('💵 総支払金額: ¥'.number_format((float) $settlement->total_payment_amount));
            $this->newLine();

            // ファイル確認
            $this->info('4. 生成ファイル確認');
            if ($settlement->hasExcelFile()) {
                $this->line("  ✅ Excel: {$settlement->excel_path}");
            } else {
                $this->warn('  ⚠️  Excel ファイルが見つかりません');
            }

            if ($settlement->hasPdfFile()) {
                $this->line("  ✅ PDF: {$settlement->pdf_path}");
            } else {
                $this->warn('  ⚠️  PDF ファイルが見つかりません');
            }

            $this->newLine();
            $this->info('✅ すべてのテスト完了');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('=== 精算書生成エラー ===');
            $this->error($e->getMessage());
            $this->newLine();
            $this->line('スタックトレース:');
            $this->line($e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
