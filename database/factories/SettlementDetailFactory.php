<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Settlement;
use App\Models\SettlementDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * 精算明細ファクトリー
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SettlementDetail>
 */
class SettlementDetailFactory extends Factory
{
    /**
     * モデルの対応するファクトリー
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = SettlementDetail::class;

    /**
     * モデルのデフォルト状態を定義
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $salesAmount = $this->faker->randomFloat(2, 10000, 500000);
        $commissionRate = $this->faker->randomElement([15, 20, 25]);
        $commissionAmount = $salesAmount * ($commissionRate / 100);
        $paymentAmount = $salesAmount - $commissionAmount;

        $salesCount = $this->faker->numberBetween(10, 100);

        // サンプル売上明細を生成
        $salesDetails = [];
        for ($i = 0; $i < min($salesCount, 5); $i++) {
            $unitPrice = $this->faker->randomFloat(0, 100, 5000);
            $quantity = $this->faker->numberBetween(1, 20);
            $amount = $unitPrice * $quantity;

            $salesDetails[] = [
                'sale_date' => $this->faker->date(),
                'product_code' => 'P'.str_pad((string) $this->faker->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
                'product_name' => $this->faker->randomElement([
                    '手づくりクッキー',
                    'さきいか',
                    '南部せんべい',
                    'りんごジュース',
                    '漬物',
                    '工芸品',
                ]),
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'amount' => $amount,
                'commission_rate' => $commissionRate,
            ];
        }

        return [
            'settlement_id' => Settlement::factory(),
            'client_code' => 'C'.str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'client_name' => $this->faker->company().'商店',
            'postal_code' => '〒'.$this->faker->postcode(),
            'address' => $this->faker->prefecture().$this->faker->city().$this->faker->streetAddress(),
            'bank_name' => $this->faker->randomElement(['みずほ銀行', '三菱UFJ銀行', '三井住友銀行', 'りそな銀行', 'ゆうちょ銀行']),
            'branch_name' => $this->faker->city().'支店',
            'account_type' => $this->faker->randomElement(['普通', '当座']),
            'account_number' => $this->faker->numerify('#######'),
            'account_name' => 'カ）'.$this->faker->company(),
            'sales_amount' => $salesAmount,
            'commission_amount' => $commissionAmount,
            'payment_amount' => $paymentAmount,
            'sales_count' => $salesCount,
            'sales_details' => $salesDetails,
        ];
    }

    /**
     * 特定の委託先コードを持つ状態
     * 
     * @param  string  $clientCode
     * @return static
     */
    public function forClient(string $clientCode): static
    {
        return $this->state(fn (array $attributes) => [
            'client_code' => $clientCode,
        ]);
    }

    /**
     * 売上金額が大きい状態
     * 
     * @return static
     */
    public function largeAmount(): static
    {
        return $this->state(function (array $attributes) {
            $salesAmount = $this->faker->randomFloat(2, 500000, 5000000);
            $commissionRate = 20;
            $commissionAmount = $salesAmount * 0.20;
            $paymentAmount = $salesAmount - $commissionAmount;

            return [
                'sales_amount' => $salesAmount,
                'commission_amount' => $commissionAmount,
                'payment_amount' => $paymentAmount,
                'sales_count' => $this->faker->numberBetween(100, 500),
            ];
        });
    }

    /**
     * 売上金額が小さい状態
     * 
     * @return static
     */
    public function smallAmount(): static
    {
        return $this->state(function (array $attributes) {
            $salesAmount = $this->faker->randomFloat(2, 1000, 10000);
            $commissionRate = 20;
            $commissionAmount = $salesAmount * 0.20;
            $paymentAmount = $salesAmount - $commissionAmount;

            return [
                'sales_amount' => $salesAmount,
                'commission_amount' => $commissionAmount,
                'payment_amount' => $paymentAmount,
                'sales_count' => $this->faker->numberBetween(1, 10),
            ];
        });
    }
}

