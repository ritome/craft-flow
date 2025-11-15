<?php

namespace Database\Factories;

use App\Models\ZenginLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ZenginLog>
 */
class ZenginLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ZenginLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-30 days', 'now');
        $filename = sprintf('zengin_%s.txt', $date->format('Ymd_His'));

        return [
            'filename' => $filename,
            'file_path' => 'zengin/' . $filename,
            'total_count' => fake()->numberBetween(1, 100),
            'total_amount' => fake()->numberBetween(10000, 10000000),
            'created_at' => $date,
            'updated_at' => $date,
        ];
    }
}
