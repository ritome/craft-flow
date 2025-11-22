<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 委託精算明細テーブル
 *
 * Issue #13: 委託先別精算データ自動変換機能
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settlement_details', function (Blueprint $table) {
            $table->id();

            // 外部キー
            $table->foreignId('settlement_id')
                ->constrained('settlements')
                ->onDelete('cascade')
                ->comment('精算履歴ID');

            // 委託先情報
            $table->string('client_code')->comment('委託先コード');
            $table->string('client_name')->comment('委託先名');
            $table->string('postal_code')->nullable()->comment('郵便番号');
            $table->text('address')->nullable()->comment('住所');

            // 銀行情報
            $table->string('bank_name')->nullable()->comment('銀行名');
            $table->string('branch_name')->nullable()->comment('支店名');
            $table->string('account_type')->nullable()->comment('口座種別');
            $table->string('account_number')->nullable()->comment('口座番号');
            $table->string('account_name')->nullable()->comment('口座名義');

            // 精算金額
            $table->decimal('sales_amount', 15, 2)->default(0)->comment('売上金額');
            $table->decimal('commission_amount', 15, 2)->default(0)->comment('手数料金額');
            $table->decimal('payment_amount', 15, 2)->default(0)->comment('支払金額');

            // 売上件数
            $table->integer('sales_count')->default(0)->comment('売上件数');

            $table->timestamps();

            // インデックス
            $table->index('settlement_id');
            $table->index('client_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlement_details');
    }
};
