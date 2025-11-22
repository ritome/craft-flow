<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReservationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 既存データを全て削除
        DB::table('reservations')->delete();

        // テスト用の予約データを作成します。
        // experience_program_id は、ExperienceProgramSeeder で挿入されたデータIDに依存します。
        // ここでは便宜上、1, 2, 3を使用します。

        $reservations = [
            [
                'experience_program_id' => 1, // 陶芸体験コース
                'reservation_date' => date('Y-m-d', strtotime('+7 days')),
                'reservation_time' => '10:00:00',
                'customer_name' => '佐藤 太郎',
                'customer_phone' => '09011112222',
                'customer_email' => 'sato.taro@example.com',
                'participant_count' => 3,
                'reservation_source' => 'hp', // 自社HP
                'notes' => '子供（5歳）が一人います。',
                'status' => 1, // 予約済み
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'experience_program_id' => 2, // 地元食材クッキング
                'reservation_date' => date('Y-m-d', strtotime('+10 days')),
                'reservation_time' => '14:30:00',
                'customer_name' => '田中 花子',
                'customer_phone' => '08033334444',
                'customer_email' => 'tanaka.hanako@example.com',
                'participant_count' => 2,
                'reservation_source' => 'jalan', // じゃらん
                'notes' => null,
                'status' => 1, // 予約済み
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'experience_program_id' => 1, // 陶芸体験コース
                'reservation_date' => date('Y-m-d', strtotime('+3 days')),
                'reservation_time' => '13:00:00',
                'customer_name' => '山田 次郎',
                'customer_phone' => '09055556666',
                'customer_email' => null,
                'participant_count' => 1,
                'reservation_source' => 'self_call', // 電話予約
                'notes' => 'サプライズでの利用です。',
                'status' => 2, // 完了
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('reservations')->insert($reservations);
    }
}
