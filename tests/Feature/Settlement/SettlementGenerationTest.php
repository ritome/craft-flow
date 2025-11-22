<?php

declare(strict_types=1);

namespace Tests\Feature\Settlement;

use App\Models\Settlement;
use App\Models\SettlementDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * 精算書生成機能のテスト
 *
 * Issue #12〜#17: 委託精算書一括発行システム
 */
class SettlementGenerationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テストの前処理
     */
    protected function setUp(): void
    {
        parent::setUp();

        // ストレージをモック化
        Storage::fake('local');
    }

    /**
     * 精算トップ画面が表示されることを確認
     */
    public function test_精算トップ画面が表示される(): void
    {
        $response = $this->get(route('settlements.index'));

        $response->assertOk();
        $response->assertSee('委託精算書一括発行');
    }

    /**
     * 必須項目が未入力の場合エラーになる
     */
    public function test_必須項目が未入力の場合エラーになる(): void
    {
        $response = $this->post(route('settlements.generate'), []);

        $response->assertSessionHasErrors([
            'billing_start_date',
            'billing_end_date',
            'customer_file',
            'sales_file',
        ]);
    }

    /**
     * 請求開始日が終了日より後の場合エラーになる
     */
    public function test_請求開始日が終了日より後の場合エラーになる(): void
    {
        $customerFile = UploadedFile::fake()->create('customers.xlsx', 100);
        $salesFile = UploadedFile::fake()->create('sales.xlsx', 100);

        $response = $this->post(route('settlements.generate'), [
            'billing_start_date' => '2025-10-31',
            'billing_end_date' => '2025-10-01',
            'customer_file' => $customerFile,
            'sales_file' => $salesFile,
        ]);

        $response->assertSessionHasErrors('billing_start_date');
    }

    /**
     * 請求終了日が未来日の場合エラーになる
     */
    public function test_請求終了日が未来日の場合エラーになる(): void
    {
        $customerFile = UploadedFile::fake()->create('customers.xlsx', 100);
        $salesFile = UploadedFile::fake()->create('sales.xlsx', 100);

        $futureDate = now()->addDays(10)->format('Y-m-d');

        $response = $this->post(route('settlements.generate'), [
            'billing_start_date' => '2025-10-01',
            'billing_end_date' => $futureDate,
            'customer_file' => $customerFile,
            'sales_file' => $salesFile,
        ]);

        $response->assertSessionHasErrors('billing_end_date');
    }

    /**
     * 請求期間が3ヶ月超の場合エラーになる
     */
    public function test_請求期間が3ヶ月超の場合エラーになる(): void
    {
        $customerFile = UploadedFile::fake()->create('customers.xlsx', 100);
        $salesFile = UploadedFile::fake()->create('sales.xlsx', 100);

        $response = $this->post(route('settlements.generate'), [
            'billing_start_date' => '2025-01-01',
            'billing_end_date' => '2025-05-01', // 4ヶ月
            'customer_file' => $customerFile,
            'sales_file' => $salesFile,
        ]);

        $response->assertSessionHasErrors('billing_end_date');
    }

    /**
     * ファイル形式が不正な場合エラーになる
     */
    public function test_ファイル形式が不正な場合エラーになる(): void
    {
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->post(route('settlements.generate'), [
            'billing_start_date' => '2025-10-01',
            'billing_end_date' => '2025-10-31',
            'customer_file' => $invalidFile,
            'sales_file' => $invalidFile,
        ]);

        $response->assertSessionHasErrors(['customer_file', 'sales_file']);
    }

    /**
     * ファイルサイズが10MBを超える場合エラーになる
     */
    public function test_ファイルサイズが10_m_bを超える場合エラーになる(): void
    {
        $largeFile = UploadedFile::fake()->create('large.xlsx', 11000); // 11MB

        $response = $this->post(route('settlements.generate'), [
            'billing_start_date' => '2025-10-01',
            'billing_end_date' => '2025-10-31',
            'customer_file' => $largeFile,
            'sales_file' => $largeFile,
        ]);

        $response->assertSessionHasErrors(['customer_file', 'sales_file']);
    }

    /**
     * 精算履歴一覧が表示される
     */
    public function test_精算履歴一覧が表示される(): void
    {
        // テストデータを作成
        Settlement::factory()
            ->has(SettlementDetail::factory()->count(5), 'details')
            ->count(3)
            ->create();

        $response = $this->get(route('settlements.history'));

        $response->assertOk();
        $response->assertSee('精算書発行履歴');
    }

    /**
     * 精算履歴が存在しない場合も正常に表示される
     */
    public function test_精算履歴が存在しない場合も正常に表示される(): void
    {
        $response = $this->get(route('settlements.history'));

        $response->assertOk();
    }

    /**
     * Excelダウンロードが正常に動作する
     */
    public function test_excelダウンロードが正常に動作する(): void
    {
        // テストデータとファイルを作成
        $settlement = Settlement::factory()
            ->withExcelFile()
            ->has(SettlementDetail::factory()->count(3), 'details')
            ->create();

        // ダミーファイルを作成
        Storage::disk('local')->put($settlement->excel_path, 'dummy excel content');

        $response = $this->get(route('settlements.download.excel', $settlement));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /**
     * PDFダウンロードが正常に動作する
     */
    public function test_pd_fダウンロードが正常に動作する(): void
    {
        // テストデータとファイルを作成
        $settlement = Settlement::factory()
            ->withPdfFile()
            ->has(SettlementDetail::factory()->count(3), 'details')
            ->create();

        // ダミーファイルを作成
        Storage::disk('local')->put($settlement->pdf_path, 'dummy pdf content');

        $response = $this->get(route('settlements.download.pdf', $settlement));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * ファイルが存在しない場合エラーが返される
     */
    public function test_ファイルが存在しない場合エラーが返される(): void
    {
        $settlement = Settlement::factory()
            ->has(SettlementDetail::factory()->count(3), 'details')
            ->create([
                'excel_path' => 'non_existent.xlsx',
            ]);

        $response = $this->get(route('settlements.download.excel', $settlement));

        $response->assertRedirect();
        $response->assertSessionHasErrors('download_error');
    }

    /**
     * 精算履歴の削除が正常に動作する
     */
    public function test_精算履歴の削除が正常に動作する(): void
    {
        $settlement = Settlement::factory()
            ->withBothFiles()
            ->has(SettlementDetail::factory()->count(3), 'details')
            ->create();

        // ダミーファイルを作成
        Storage::disk('local')->put($settlement->excel_path, 'dummy excel');
        Storage::disk('local')->put($settlement->pdf_path, 'dummy pdf');

        $response = $this->delete(route('settlements.destroy', $settlement));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // DBから削除されていることを確認
        $this->assertDatabaseMissing('settlements', ['id' => $settlement->id]);

        // 関連する明細も削除されていることを確認（カスケード）
        $this->assertDatabaseMissing('settlement_details', ['settlement_id' => $settlement->id]);

        // ファイルも削除されていることを確認
        Storage::disk('local')->assertMissing($settlement->excel_path);
        Storage::disk('local')->assertMissing($settlement->pdf_path);
    }

    /**
     * 商品明細が個別レコードとして保存されることを確認
     */
    public function test_商品明細が個別レコードとして保存される(): void
    {
        // 同じ商品コードを持つ売上明細を含むデータ
        $salesDetails = [
            [
                'product_code' => 'P001',
                'product_name' => 'テスト商品A',
                'unit_price' => 100,
                'quantity' => 5,
                'amount' => 500,
            ],
            [
                'product_code' => 'P001',
                'product_name' => 'テスト商品A',
                'unit_price' => 100,
                'quantity' => 3,
                'amount' => 300,
            ],
            [
                'product_code' => 'P002',
                'product_name' => 'テスト商品B',
                'unit_price' => 200,
                'quantity' => 2,
                'amount' => 400,
            ],
        ];

        $settlement = Settlement::factory()->create();
        $detail = SettlementDetail::factory()
            ->for($settlement)
            ->create([
                'sales_details' => $salesDetails,
                'sales_count' => 3, // 売上レコード数
            ]);

        // 明細を取得して検証
        $details = $detail->sales_details;

        // 個別レコードとして保存されていることを確認
        $this->assertIsArray($details);
        $this->assertCount(3, $details);

        // 同じ商品コード（P001）が2つ存在することを確認
        $p001Count = count(array_filter($details, fn ($d) => $d['product_code'] === 'P001'));
        $this->assertEquals(2, $p001Count);
    }
}
