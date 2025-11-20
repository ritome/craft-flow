<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Settlement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Settlement>
 */
class SettlementFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Settlement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 year', 'now');
        $endDate = fake()->dateTimeBetween($startDate, '+1 month');

        return [
            'billing_start_date' => $startDate,
            'billing_end_date' => $endDate,
            'client_count' => fake()->numberBetween(1, 10),
            'excel_path' => 'settlements/settlement_'.fake()->unique()->uuid().'.xlsx',
            'pdf_path' => 'settlements/settlement_'.fake()->unique()->uuid().'.pdf',
            'total_sales_amount' => fake()->randomFloat(2, 10000, 1000000),
            'total_commission' => fake()->randomFloat(2, 1000, 100000),
            'total_payment_amount' => fake()->randomFloat(2, 9000, 900000),
        ];
    }
}
