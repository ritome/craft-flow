<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Settlement;

// 最新の精算書を取得
$settlement = Settlement::with('details')->latest()->first();

if (! $settlement) {
    echo "精算書が見つかりません\n";
    exit;
}

echo "=== 精算書情報 ===\n";
echo "ID: {$settlement->id}\n";
echo "精算番号: {$settlement->settlement_number}\n";
echo "期間: {$settlement->billing_start_date} 〜 {$settlement->billing_end_date}\n";
echo "委託先数: {$settlement->client_count}\n";
echo '合計売上: '.number_format($settlement->total_sales_amount)."\n";
echo '合計手数料: '.number_format($settlement->total_commission)."\n";
echo '合計支払額: '.number_format($settlement->total_payment_amount)."\n";
echo "\n";

echo "=== 委託先別明細 ===\n";
foreach ($settlement->details as $detail) {
    echo "\n{$detail->client_code}: {$detail->client_name}\n";
    echo '  売上金額: '.number_format($detail->sales_amount)."\n";
    echo '  手数料: '.number_format($detail->commission_amount)."\n";
    echo '  支払額: '.number_format($detail->payment_amount)."\n";
    echo "  売上件数: {$detail->sales_count}\n";
    $salesDetails = $detail->sales_details;
    if (is_array($salesDetails) && count($salesDetails) > 0) {
        echo '  売上明細件数: '.count($salesDetails)."\n";
        // 最初の3件を表示
        foreach (array_slice($salesDetails, 0, 3) as $sale) {
            echo "    - {$sale['product_name']}: ".number_format($sale['amount'] ?? 0)."円\n";
        }
    }
}
