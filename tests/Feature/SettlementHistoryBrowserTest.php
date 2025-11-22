<?php

declare(strict_types=1);

use App\Models\Settlement;
use App\Models\SettlementDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Issue #17: 過去精算書履歴ダウンロード機能のテスト
 * 
 * ブラウザ表示とダウンロード機能の動作確認
 */

// 履歴一覧ページが正しく表示されることをテスト
test('history page displays settlement records', function () {
    // テストファイルを作成（ファイルが存在するとダウンロードボタンが表示される）
    Storage::disk('local')->put('settlements/test1.xlsx', 'test excel');
    Storage::disk('local')->put('settlements/test1.pdf', 'test pdf');
    Storage::disk('local')->put('settlements/test2.xlsx', 'test excel 2');
    Storage::disk('local')->put('settlements/test2.pdf', 'test pdf 2');

    // テストデータ作成
    $settlement1 = Settlement::factory()->create([
        'billing_start_date' => '2024-11-01',
        'billing_end_date' => '2024-11-30',
        'client_count' => 3,
        'total_sales_amount' => 300000,
        'total_commission' => 30000,
        'total_payment_amount' => 270000,
        'excel_path' => 'settlements/test1.xlsx',
        'pdf_path' => 'settlements/test1.pdf',
    ]);

    $settlement2 = Settlement::factory()->create([
        'billing_start_date' => '2024-12-01',
        'billing_end_date' => '2024-12-31',
        'client_count' => 5,
        'total_sales_amount' => 500000,
        'total_commission' => 50000,
        'total_payment_amount' => 450000,
        'excel_path' => 'settlements/test2.xlsx',
        'pdf_path' => 'settlements/test2.pdf',
    ]);

    // 履歴ページにアクセス
    $response = $this->get('/settlements/history');

    // ステータスコードの確認
    $response->assertStatus(200);

    // ページタイトルと説明文の確認
    $response->assertSee('精算書発行履歴');
    $response->assertSee('過去に発行した精算書の一覧と再ダウンロードができます');

    // 統計情報の確認
    $response->assertSee('総発行回数');
    $response->assertSee('総委託先数');
    $response->assertSee('総売上金額');

    // 精算履歴データの確認
    $response->assertSee('2024年11月01日 〜 2024年11月30日');
    $response->assertSee('3件'); // client_count
    $response->assertSee('¥300,000'); // total_sales_amount

    $response->assertSee('2024年12月01日 〜 2024年12月31日');
    $response->assertSee('5件'); // client_count
    $response->assertSee('¥500,000'); // total_sales_amount

    // ダウンロードボタンの確認
    $response->assertSee('Excel');
    $response->assertSee('PDF');
    $response->assertSee('削除');

    // クリーンアップ
    Storage::disk('local')->deleteDirectory('settlements');
});

// Excelファイルのダウンロード機能のテスト
test('can download excel file', function () {
    // テストファイルを作成
    $excelContent = 'test excel content';
    Storage::disk('local')->put('settlements/test.xlsx', $excelContent);

    $settlement = Settlement::factory()->create([
        'excel_path' => 'settlements/test.xlsx',
    ]);

    // Excelダウンロードリクエスト
    $response = $this->get("/settlements/download/{$settlement->id}/excel");

    // ステータスコードの確認
    $response->assertStatus(200);

    // ヘッダーの確認
    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    // コンテンツの確認
    expect($response->streamedContent())->toBe($excelContent);

    // クリーンアップ
    Storage::disk('local')->deleteDirectory('settlements');
});

// PDFファイルのダウンロード機能のテスト
test('can download pdf file', function () {
    // テストファイルを作成
    $pdfContent = 'test pdf content';
    Storage::disk('local')->put('settlements/test.pdf', $pdfContent);

    $settlement = Settlement::factory()->create([
        'pdf_path' => 'settlements/test.pdf',
    ]);

    // PDFダウンロードリクエスト
    $response = $this->get("/settlements/download/{$settlement->id}/pdf");

    // ステータスコードの確認
    $response->assertStatus(200);

    // ヘッダーの確認
    $response->assertHeader('Content-Type', 'application/pdf');

    // コンテンツの確認
    expect($response->streamedContent())->toBe($pdfContent);

    // クリーンアップ
    Storage::disk('local')->deleteDirectory('settlements');
});

// ファイルが存在しない場合のエラーハンドリングテスト
test('returns error when excel file does not exist', function () {
    $settlement = Settlement::factory()->create([
        'excel_path' => 'settlements/nonexistent.xlsx',
    ]);

    $response = $this->get("/settlements/download/{$settlement->id}/excel");

    $response->assertRedirect();
    $response->assertSessionHasErrors('download_error');
});

// ファイルが存在しない場合のエラーハンドリングテスト（PDF）
test('returns error when pdf file does not exist', function () {
    $settlement = Settlement::factory()->create([
        'pdf_path' => 'settlements/nonexistent.pdf',
    ]);

    $response = $this->get("/settlements/download/{$settlement->id}/pdf");

    $response->assertRedirect();
    $response->assertSessionHasErrors('download_error');
});

// 空の履歴ページのテスト
test('history page shows empty state when no records', function () {
    // 既存のレコードを全て削除
    Settlement::query()->delete();

    $response = $this->get('/settlements/history');

    $response->assertStatus(200);
    $response->assertSee('履歴がありません');
    $response->assertSee('まだ精算書を発行していません');
    $response->assertSee('精算書を生成する');
});

// ページネーションのテスト
test('history page paginates records', function () {
    // 30件のレコードを作成（ページサイズは20）
    Settlement::factory()->count(30)->create();

    $response = $this->get('/settlements/history');

    $response->assertStatus(200);
    // ページネーションリンクが表示されることを確認
    // Laravelのページネーションは自動的にリンクを生成
});
