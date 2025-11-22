<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 委託精算明細モデル
 *
 * Issue #13: 委託先別精算データ自動変換機能
 *
 * @property int $id
 * @property int $settlement_id
 * @property string $client_code 委託先コード
 * @property string $client_name 委託先名
 * @property string|null $postal_code 郵便番号
 * @property string|null $address 住所
 * @property string|null $bank_name 銀行名
 * @property string|null $branch_name 支店名
 * @property string|null $account_type 口座種別
 * @property string|null $account_number 口座番号
 * @property string|null $account_name 口座名義
 * @property float $sales_amount 売上金額
 * @property float $commission_amount 手数料金額
 * @property float $payment_amount 支払金額
 * @property int $sales_count 売上件数
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class SettlementDetail extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'settlement_id',
        'client_code',
        'client_name',
        'postal_code',
        'address',
        'bank_name',
        'branch_name',
        'account_type',
        'account_number',
        'account_name',
        'sales_amount',
        'commission_amount',
        'payment_amount',
        'sales_count',
        'sales_details',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settlement_id' => 'integer',
            'sales_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'payment_amount' => 'decimal:2',
            'sales_count' => 'integer',
            'sales_details' => 'array',
        ];
    }

    /**
     * 精算履歴とのリレーション
     */
    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }
}
