<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 開発環境用のテストユーザーを作成
        User::factory()->create([
            'name' => 'Test User2',
            'email' => 'test2@example.com',
        ]);
        
        // --- データの呼び出し順序を保証 ---
        // 外部キー制約エラーを避けるため、参照されるテーブル(プログラム)を先に実行します。
        $this->call([
            ExperienceProgramSeeder::class, // 1. プログラムデータを先に作成
            ReservationSeeder::class,       // 2. その後、予約データを安全に作成
        ]);
    }
}
