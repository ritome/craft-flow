<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

/**
 * 委託先別精算データDTO
 * 
 * Issue #13: 委託先別精算データ自動変換機能
 * 
 * 計算済みの精算データを保持し、Excel/PDF生成に使用する
 */
class SettlementClientData
{
    /**
     * コンストラクタ
     * 
     * @param  string  $clientCode  委託先コード
     * @param  string  $clientName  委託先名
     * @param  string|null  $postalCode  郵便番号
     * @param  string|null  $address  住所
     * @param  string|null  $bankName  銀行名
     * @param  string|null  $branchName  支店名
     * @param  string|null  $accountType  口座種別
     * @param  string|null  $accountNumber  口座番号
     * @param  string|null  $accountName  口座名義
     * @param  float  $salesAmount  売上金額合計
     * @param  float  $commissionAmount  手数料合計
     * @param  float  $paymentAmount  支払金額合計
     * @param  int  $salesCount  売上件数
     * @param  array  $salesDetails  売上明細配列
     */
    public function __construct(
        public readonly string $clientCode,
        public readonly string $clientName,
        public readonly ?string $postalCode,
        public readonly ?string $address,
        public readonly ?string $bankName,
        public readonly ?string $branchName,
        public readonly ?string $accountType,
        public readonly ?string $accountNumber,
        public readonly ?string $accountName,
        public readonly float $salesAmount,
        public readonly float $commissionAmount,
        public readonly float $paymentAmount,
        public readonly int $salesCount,
        public readonly array $salesDetails = [],
    ) {}

    /**
     * 配列から DTO を生成
     * 
     * @param  array  $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            clientCode: (string) ($data['client_code'] ?? ''),
            clientName: (string) ($data['client_name'] ?? ''),
            postalCode: isset($data['postal_code']) ? (string) $data['postal_code'] : null,
            address: isset($data['address']) ? (string) $data['address'] : null,
            bankName: isset($data['bank_name']) ? (string) $data['bank_name'] : null,
            branchName: isset($data['branch_name']) ? (string) $data['branch_name'] : null,
            accountType: isset($data['account_type']) ? (string) $data['account_type'] : null,
            accountNumber: isset($data['account_number']) ? (string) $data['account_number'] : null,
            accountName: isset($data['account_name']) ? (string) $data['account_name'] : null,
            salesAmount: (float) ($data['sales_amount'] ?? 0),
            commissionAmount: (float) ($data['commission_amount'] ?? 0),
            paymentAmount: (float) ($data['payment_amount'] ?? 0),
            salesCount: (int) ($data['sales_count'] ?? 0),
            salesDetails: $data['sales_details'] ?? [],
        );
    }

    /**
     * DTO を配列に変換
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'client_code' => $this->clientCode,
            'client_name' => $this->clientName,
            'postal_code' => $this->postalCode,
            'address' => $this->address,
            'bank_name' => $this->bankName,
            'branch_name' => $this->branchName,
            'account_type' => $this->accountType,
            'account_number' => $this->accountNumber,
            'account_name' => $this->accountName,
            'sales_amount' => $this->salesAmount,
            'commission_amount' => $this->commissionAmount,
            'payment_amount' => $this->paymentAmount,
            'sales_count' => $this->salesCount,
            'sales_details' => $this->salesDetails,
        ];
    }

    /**
     * 精算書の表示用期間文字列を取得
     * 
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @return string
     */
    public static function formatBillingPeriod(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): string
    {
        return $startDate->format('Y年n月j日').' 〜 '.$endDate->format('Y年n月j日');
    }

    /**
     * 郵便番号をフォーマット（〒マークを付与）
     * 
     * @return string
     */
    public function getFormattedPostalCode(): string
    {
        if (! $this->postalCode) {
            return '';
        }

        // 既に〒マークが付いている場合はそのまま返す
        if (str_starts_with($this->postalCode, '〒')) {
            return $this->postalCode;
        }

        return '〒'.$this->postalCode;
    }

    /**
     * 委託先の完全な住所を取得
     * 
     * @return string
     */
    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->getFormattedPostalCode(),
            $this->address,
        ]);

        return implode(' ', $parts);
    }

    /**
     * 振込先情報の完全な文字列を取得
     * 
     * @return string
     */
    public function getFullBankInfo(): string
    {
        $parts = array_filter([
            $this->bankName,
            $this->branchName,
            $this->accountType,
            $this->accountNumber,
        ]);

        return implode('  ', $parts);
    }
}

