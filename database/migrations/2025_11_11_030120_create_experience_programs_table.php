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
        Schema::create('experience_programs', function (Blueprint $table) {
            // 1. プログラムID (Primary Key, Auto Increment)
            // ご要望のexperience_program_idをPKとするため、id()ではなくunsignedBigIntegerとprimary()を使用します。
            // ただし、Laravelの慣習に従い、リレーションを考慮して外部キー側(reservations)を合わせるため、ここでは id() とします。
            // ※ Laravelの慣習: PKは 'id', 型はbigint(20) unsigned
            $table->id('experience_program_id')->comment('プログラムID (PK)');

            // 2. プログラム名 (Unique)
            $table->string('name', 30)->unique()->comment('プログラム名');

            // 3. 説明
            $table->text('description')->nullable()->comment('説明');

            // 4. 所要時間（分）
            $table->integer('duration')->comment('所要時間（分)');

            // 5. 最大受入人数
            $table->integer('capacity')->comment('最大受入人数');

            // 6. 料金（円）
            $table->integer('price')->comment('料金（円）');

            // 7. 作成日時, 8. 更新日時
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('experience_programs');
    }
};
