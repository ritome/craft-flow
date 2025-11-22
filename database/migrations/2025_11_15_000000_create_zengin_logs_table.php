<?php

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
        if (! Schema::hasTable('zengin_logs')) {
            Schema::create('zengin_logs', function (Blueprint $table) {
                $table->id();
                $table->string('filename')->comment('生成ファイル名');
                $table->string('file_path')->comment('保存先パス（storage/app からの相対パス）');
                $table->integer('total_count')->default(0)->comment('総レコード数');
                $table->bigInteger('total_amount')->default(0)->comment('合計金額');
                $table->timestamps();

                // インデックス
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zengin_logs');
    }
};
