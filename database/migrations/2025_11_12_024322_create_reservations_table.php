<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            // 1. id
            $table->id(); // 符号なしBIGINT (Primary Key, Auto Increment)

            // 2. プログラムID

            $table->unsignedBigInteger('experience_program_id')->comment('体験プログラムID');
            $table->foreign('experience_program_id')
                ->references('experience_program_id') // ここを 'id' から 'experience_program_id' に変更！
                ->on('experience_programs');
            // 3. 予約日, 4. 予約時刻
            // reserved_atとしてdatetimeで一元管理すると、後の操作が楽になります
            // または、ご要望通りに日付と時刻を分ける
            $table->date('reservation_date')->comment('予約日');
            $table->time('reservation_time')->comment('予約時刻');
            // $table->dateTime('reserved_at')->comment('予約日時'); // (代替案)

            // 5. 予約者名
            $table->string('customer_name', 255)->comment('予約者名');

            // 6. 電話番号
            $table->string('customer_phone', 20)->nullable()->comment('電話番号');

            // 7. メール
            $table->string('customer_email', 255)->nullable()->comment('メール');

            // 8. 参加人数
            $table->integer('participant_count')->default(1)->comment('参加人数');

            // 9. 予約経路 (VARCHAR(255) または ENUM が良い)
            $table->string('reservation_source', 255)->comment('予約経路');
            // $table->enum('reservation_source', ['jalan', 'asoview', 'hp', 'self_call', 'center_call'])->comment('予約経路'); // (代替案)

            // 10. 予約状態 (INT)
            // 1:予約済み, 2:キャンセル, 3:完了 などのコード値
            $table->integer('status')->default(1)->comment('予約状態 (1:予約済み, 2:キャンセル, 3:完了)');

            // 11. 備考
            $table->text('notes')->nullable()->comment('備考');

            // 12. 作成日時, 13. 更新日時
            $table->timestamps();

            // 14. 論理削除フラグ, 15. 削除日時 (Soft Deletes)
            $table->softDeletes();
            // 注意: softDeletes()を使うと、削除フラグ用のカラムは自動生成されません。
            // 削除フラグが必要な場合は、別途integerのカラムを定義してください。
            // $table->integer('delete_flg')->default(0)->comment('論理削除フラグ (0: 有効, 1: 削除)'); // (代替案)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
