<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Settlement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * 精算履歴ファクトリー
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Settlement>
 */
class SettlementFactory extends Factory
{
    /**
     * モデルの対応するファクトリー
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Settlement::class;

    /**
     * モデルのデフォルト状態を定義
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $billingStart = $this->faker->dateTimeBetween('-1 year', 'now');
        $billingEnd = (clone $billingStart)->modify('+1 month');
        $paymentDate = (clone $billingEnd)->modify('+40 days');

        $totalSales = $this->faker->randomFloat(2, 100000, 10000000);
        $commissionRate = 0.20; // 20%
        $totalCommission = $totalSales * $commissionRate;
        $totalPayment = $totalSales - $totalCommission;

        return [
            'settlement_number' => $this->generateSettlementNumber($billingStart),
            'billing_start_date' => $billingStart,
            'billing_end_date' => $billingEnd,
            'payment_date' => $paymentDate,
            'client_count' => $this->faker->numberBetween(10, 90),
            'total_sales_amount' => $totalSales,
            'total_commission' => $totalCommission,
            'total_payment_amount' => $totalPayment,
        ];
    }

    /**
     * 精算番号を生成
     * 
     * @param  \DateTime  $billingStartDate
     * @return string
     */
    private function generateSettlementNumber(\DateTime $billingStartDate): string
    {
        $date = \Carbon\Carbon::instance($billingStartDate);

        return sprintf(
            '%s-C%03d',
            $date->format('Y-m'),
            $this->faker->numberBetween(1, 999)
        );
    }

    /**
     * Excelファイルが存在する状態
     * 
     * @return static
     */
    public function withExcelFile(): static
    {
        return $this->state(fn (array $attributes) => [
            'excel_path' => 'settlements/settlement_'.date('Ymd').'.xlsx',
        ]);
    }

    /**
     * PDFファイルが存在する状態
     * 
     * @return static
     */
    public function withPdfFile(): static
    {
        return $this->state(fn (array $attributes) => [
            'pdf_path' => 'settlements/settlement_'.date('Ymd').'.pdf',
        ]);
    }

    /**
     * Excel/PDF両方のファイルが存在する状態
     * 
     * @return static
     */
    public function withBothFiles(): static
    {
        return $this->withExcelFile()->withPdfFile();
    }
}
