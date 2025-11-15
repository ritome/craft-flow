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
        Schema::create('zengin_logs', function (Blueprint $table) {
            $table->id();
            $table->string('filename')->comment('生成ファイル名');
            $table->integer('total_count')->default(0)->comment('総レコード数');
            $table->bigInteger('total_amount')->default(0)->comment('合計金額');
            $table->timestamps();

            // インデックス
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zengin_logs');
    }
};
