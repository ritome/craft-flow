<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * settlement_details テーブルに sales_details カラムを追加
 *
 * 個別の売上明細データをJSON形式で保存
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('settlement_details', function (Blueprint $table) {
            $table->json('sales_details')->nullable()->after('sales_count')->comment('売上明細（JSON）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settlement_details', function (Blueprint $table) {
            $table->dropColumn('sales_details');
        });
    }
};
