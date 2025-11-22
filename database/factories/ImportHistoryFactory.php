<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ImportHistory>
 */
class ImportHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileCount = $this->faker->numberBetween(1, 10);
        $failedCount = $this->faker->numberBetween(0, 2);
        $successCount = $fileCount - $failedCount;

        return [
            'import_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'file_count' => $fileCount,
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'excel_path' => 'exports/test_'.uniqid().'.xlsx',
            'file_details' => [
                [
                    'file_name' => 'test_file_1.pdf',
                    'status' => 'success',
                    'sales' => $this->faker->numberBetween(1000, 50000),
                ],
            ],
            'total_sales' => $this->faker->randomFloat(2, 5000, 200000),
        ];
    }
}
