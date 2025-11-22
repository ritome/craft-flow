<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 委託精算書履歴テーブル
 *
 * Issue #16: 精算書発行履歴保存機能
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();

            // 請求期間
            $table->date('billing_start_date')->comment('請求開始日');
            $table->date('billing_end_date')->comment('請求終了日');

            // 委託先情報
            $table->integer('client_count')->default(0)->comment('委託先数');

            // ファイルパス
            $table->string('excel_path')->nullable()->comment('Excel ファイルパス');
            $table->string('pdf_path')->nullable()->comment('PDF ファイルパス');

            // 統計情報
            $table->decimal('total_sales_amount', 15, 2)->default(0)->comment('総売上金額');
            $table->decimal('total_commission', 15, 2)->default(0)->comment('総手数料');
            $table->decimal('total_payment_amount', 15, 2)->default(0)->comment('総支払金額');

            $table->timestamps();

            // インデックス
            $table->index('billing_start_date');
            $table->index('billing_end_date');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
