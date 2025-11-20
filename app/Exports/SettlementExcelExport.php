<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Settlement;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * 精算書 Excel エクスポート
 * 
 * Issue #14: 月次委託精算書一括生成機能
 * 
 * 1委託先 = 1シート の構成
 */
class SettlementExcelExport implements WithMultipleSheets
{
    /**
     * コンストラクタ
     * 
     * @param  Settlement  $settlement
     * @param  array  $settlementData
     */
    public function __construct(
        private readonly Settlement $settlement,
        private readonly array $settlementData
    ) {}

    /**
     * 複数シートを生成
     * 
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];

        // settlementData が空の場合、DBから取得
        $data = $this->settlementData;
        if (empty($data)) {
            $data = $this->convertDetailsToArray();
        }

        // サマリーシート
        $sheets[] = new SettlementSummarySheet($this->settlement, $data);

        // 委託先ごとのシート
        foreach ($data as $clientCode => $clientData) {
            $sheets[] = new SettlementClientSheet($this->settlement, $clientData);
        }

        return $sheets;
    }

    /**
     * SettlementDetail から配列形式に変換
     * 
     * @return array
     */
    private function convertDetailsToArray(): array
    {
        $data = [];
        
        foreach ($this->settlement->details as $detail) {
            $data[$detail->client_code] = [
                'client_code' => $detail->client_code,
                'client_name' => $detail->client_name,
                'postal_code' => $detail->postal_code ?? '',
                'address' => $detail->address ?? '',
                'bank_name' => $detail->bank_name ?? '',
                'branch_name' => $detail->branch_name ?? '',
                'account_type' => $detail->account_type ?? '',
                'account_number' => $detail->account_number ?? '',
                'account_name' => $detail->account_name ?? '',
                'sales_amount' => $detail->sales_amount,
                'commission_amount' => $detail->commission_amount,
                'payment_amount' => $detail->payment_amount,
                'sales_count' => $detail->sales_count,
                'sales_details' => $detail->sales_details ?? [],
            ];
        }

        return $data;
    }
}

/**
 * サマリーシート
 */
class SettlementSummarySheet implements FromArray
{
    public function __construct(
        private readonly Settlement $settlement,
        private readonly array $settlementData
    ) {}

    public function array(): array
    {
        $data = [];

        // ヘッダー
        $data[] = ['委託精算書サマリー'];
        $data[] = [];
        $data[] = ['請求期間', $this->settlement->billing_start_date->format('Y年m月d日').' 〜 '.$this->settlement->billing_end_date->format('Y年m月d日')];
        $data[] = ['委託先数', count($this->settlementData).'件'];
        $data[] = ['総売上金額', '¥'.number_format((float) $this->settlement->total_sales_amount)];
        $data[] = ['総手数料', '¥'.number_format((float) $this->settlement->total_commission)];
        $data[] = ['総支払金額', '¥'.number_format((float) $this->settlement->total_payment_amount)];
        $data[] = [];

        // 委託先一覧
        $data[] = ['委託先コード', '委託先名', '売上金額', '手数料', '支払金額', '売上件数'];

        foreach ($this->settlementData as $clientData) {
            $data[] = [
                $clientData['client_code'],
                $clientData['client_name'],
                (float) $clientData['sales_amount'],
                (float) $clientData['commission_amount'],
                (float) $clientData['payment_amount'],
                $clientData['sales_count'],
            ];
        }

        return $data;
    }
}

/**
 * 委託先別シート
 */
class SettlementClientSheet implements FromArray
{
    public function __construct(
        private readonly Settlement $settlement,
        private readonly array $clientData
    ) {}

    public function array(): array
    {
        $data = [];

        // ヘッダー
        $data[] = ['委託精算書'];
        $data[] = [];
        $data[] = ['請求期間', $this->settlement->billing_start_date->format('Y年m月d日').' 〜 '.$this->settlement->billing_end_date->format('Y年m月d日')];
        $data[] = [];

        // 委託先情報
        $data[] = ['委託先コード', $this->clientData['client_code']];
        $data[] = ['委託先名', $this->clientData['client_name']];
        $data[] = ['郵便番号', $this->clientData['postal_code']];
        $data[] = ['住所', $this->clientData['address']];
        $data[] = [];

        // 銀行情報
        $data[] = ['銀行名', $this->clientData['bank_name']];
        $data[] = ['支店名', $this->clientData['branch_name']];
        $data[] = ['口座種別', $this->clientData['account_type']];
        $data[] = ['口座番号', $this->clientData['account_number']];
        $data[] = ['口座名義', $this->clientData['account_name']];
        $data[] = [];

            // 精算情報
            $data[] = ['売上金額', '¥'.number_format((float) $this->clientData['sales_amount'])];
            $data[] = ['手数料', '¥'.number_format((float) $this->clientData['commission_amount'])];
            $data[] = ['支払金額', '¥'.number_format((float) $this->clientData['payment_amount'])];
            $data[] = [];

        // 売上明細
        $data[] = ['売上日', '商品名', '単価', '数量', '金額', '手数料率'];

        foreach ($this->clientData['sales_details'] as $sale) {
            $data[] = [
                $sale['sale_date'] ?? '',
                $sale['product_name'] ?? '',
                $sale['unit_price'] ?? 0,
                $sale['quantity'] ?? 0,
                $sale['amount'] ?? 0,
                $sale['commission_rate'] ?? 0,
            ];
        }

        return $data;
    }
}

