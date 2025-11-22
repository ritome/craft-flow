<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExperienceProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 既存データを全て削除
        DB::table('experience_programs')->delete();

        $programs = [
            [
                'name' => '陶芸体験コース',
                'description' => '手びねりでオリジナルの器を作る体験です。初心者歓迎。',
                'duration' => 120, // 2時間
                'capacity' => 8,
                'price' => 4500,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '地元食材クッキング',
                'description' => '地元の新鮮な野菜を使った伝統料理を調理し、試食する体験です。',
                'duration' => 180, // 3時間
                'capacity' => 12,
                'price' => 6000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '藍染めワークショップ',
                'description' => '伝統的な技法でハンカチやTシャツを染めます。手ぶらで参加可能です。',
                'duration' => 90, // 1時間半
                'capacity' => 6,
                'price' => 3800,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('experience_programs')->insert($programs);

        // Eloquentモデルを使用する場合の例（今回のコードではDBファサードを使用）
        /*
        foreach ($programs as $programData) {
            ExperienceProgram::create($programData);
        }
        */
    }
}
