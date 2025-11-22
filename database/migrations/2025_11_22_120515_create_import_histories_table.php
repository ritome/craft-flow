<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('import_histories', function (Blueprint $table) {
            $table->id();
            $table->dateTime('import_date')->comment('集計実行日時');
            $table->integer('file_count')->default(0)->comment('アップロードファイル数');
            $table->integer('success_count')->default(0)->comment('処理成功ファイル数');
            $table->integer('failed_count')->default(0)->comment('処理失敗ファイル数');
            $table->string('excel_path')->comment('生成されたExcelファイルパス');
            $table->json('file_details')->nullable()->comment('処理ファイル詳細（JSON）');
            $table->decimal('total_sales', 15, 2)->default(0)->comment('売上合計金額');
            $table->timestamps();

            // インデックス
            $table->index('import_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_histories');
    }
};
