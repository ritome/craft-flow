<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Settlement;
use Mpdf\Mpdf;

/**
 * 精算書 PDF エクスポート
 * 
 * Issue #14: 月次委託精算書一括生成機能
 * 
 * 複数委託先を1PDFにまとめる
 * mPDFを使用して日本語を完全サポート
 */
class SettlementPdfExport
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
     * PDF を生成
     * 
     * @return string バイナリデータ
     */
    public function generate(): string
    {
        // settlementData が空の場合、DBから取得
        $data = $this->settlementData;
        if (empty($data)) {
            $data = $this->convertDetailsToArray();
        }

        \Log::info('Generating PDF with mPDF', [
            'settlement_id' => $this->settlement->id,
            'data_count' => count($data),
            'has_data' => !empty($data),
        ]);

        // HTMLをレンダリング
        $html = view('settlements.pdf', [
            'settlement' => $this->settlement,
            'settlementData' => $data,
        ])->render();

        // mPDFで日本語PDFを生成
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);

        $mpdf->WriteHTML($html);
        
        return $mpdf->Output('', 'S'); // 'S' = 文字列として返す
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



