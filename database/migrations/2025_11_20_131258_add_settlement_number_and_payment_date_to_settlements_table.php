<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * settlementsテーブルに精算番号と振込予定日を追加
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // カラムが存在しない場合のみ追加
        if (!Schema::hasColumn('settlements', 'settlement_number')) {
            Schema::table('settlements', function (Blueprint $table) {
                $table->string('settlement_number')->nullable()->after('id')->comment('精算番号（例：2025-10-C001）');
            });
        }
        
        if (!Schema::hasColumn('settlements', 'payment_date')) {
            Schema::table('settlements', function (Blueprint $table) {
                $table->date('payment_date')->nullable()->after('billing_end_date')->comment('振込予定日');
            });
        }

        // 既存レコードに精算番号を生成
        $settlements = DB::table('settlements')->orderBy('id')->get();
        foreach ($settlements as $settlement) {
            $date = \Carbon\Carbon::parse($settlement->billing_start_date);
            $yearMonth = $date->format('Y-m');
            
            // その年月の連番を取得
            $count = DB::table('settlements')
                ->where('id', '<=', $settlement->id)
                ->whereYear('billing_start_date', $date->year)
                ->whereMonth('billing_start_date', $date->month)
                ->count();
            
            $settlementNumber = sprintf('%s-C%03d', $yearMonth, $count);
            
            // 振込予定日を計算（精算期間終了日 + 40日）
            $paymentDate = \Carbon\Carbon::parse($settlement->billing_end_date)->addDays(40);
            
            DB::table('settlements')
                ->where('id', $settlement->id)
                ->update([
                    'settlement_number' => $settlementNumber,
                    'payment_date' => $paymentDate,
                ]);
        }

        // ユニーク制約を追加
        Schema::table('settlements', function (Blueprint $table) {
            $table->string('settlement_number')->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settlements', function (Blueprint $table) {
            $table->dropColumn(['settlement_number', 'payment_date']);
        });
    }
};
