<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>委託販売精算書</title>
    <style>
        @page {
            margin: 15mm;
        }
        
        body {
            font-family: 'ipaexg', sans-serif;
            font-size: 11px;
            line-height: 1.6;
            color: #000;
        }
        
        .header {
            margin-bottom: 10px;
        }
        
        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .issuer-info {
            width: 60%;
        }
        
        .settlement-info {
            width: 35%;
            text-align: right;
        }
        
        .settlement-info table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .settlement-info td {
            padding: 5px;
            background-color: #f5f5f5;
        }
        
        .client-section {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            align-items: flex-start;
        }
        
        .client-info {
            width: 55%;
        }
        
        .client-info h3 {
            margin: 0 0 10px 0;
            font-size: 12px;
            font-weight: bold;
        }
        
        .payment-box {
            width: 40%;
            border: 3px solid #4472C4;
            padding: 10px;
            background-color: #FFFFCC;
        }
        
        .payment-box h3 {
            margin: 0 0 10px 0;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
        }
        
        .payment-amount {
            font-size: 24px;
            font-weight: bold;
            text-align: right;
        }
        
        .period-section {
            margin: 20px 0 10px 0;
            font-weight: bold;
        }
        
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        .details-table th {
            background-color: #4472C4;
            color: white;
            padding: 8px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #4472C4;
        }
        
        .details-table td {
            padding: 6px;
            border: 1px solid #ccc;
        }
        
        .details-table td.text-right {
            text-align: right;
        }
        
        .details-table td.text-center {
            text-align: center;
        }
        
        .calculation-section {
            margin-top: 20px;
        }
        
        .calculation-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .calculation-table td {
            padding: 6px;
        }
        
        .calculation-table td:last-child {
            text-align: right;
            width: 120px;
        }
        
        .calculation-table .subtotal-row td {
            font-weight: bold;
        }
        
        .calculation-table .payment-row {
            background-color: #4472C4;
            color: white;
            font-weight: bold;
        }
        
        .bank-info-section {
            margin-top: 20px;
        }
        
        .bank-info-section h4 {
            margin: 10px 0 5px 0;
            font-size: 12px;
            font-weight: bold;
        }
        
        .note {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
        }
        
        /* ページブレイク制御 */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    @foreach ($settlementData as $clientData)
    @php
        $issuer = config('settlement.issuer');
        $salesTotal = (float) $clientData['sales_amount'];
        $commissionRate = config('settlement.calculation.commission_rate', 20) / 100;  // パーセントを小数に変換
        $taxRate = config('settlement.calculation.tax_rate', 10) / 100;  // パーセントを小数に変換
        $transferFee = config('settlement.calculation.transfer_fee', 440);
        
        $commission = round($salesTotal * $commissionRate);
        $amountAfterCommission = $salesTotal - $commission;
        $tax = round($amountAfterCommission * $taxRate);
        $paymentAmount = $amountAfterCommission + $tax - $transferFee;
    @endphp
    
    <div class="page @if(!$loop->last) page-break @endif">
        <div class="title">委託販売精算書</div>
        
        <div class="info-section">
            <div class="issuer-info">
                <div><strong>{{ $issuer['name'] }}</strong></div>
                <div>{{ $issuer['postal_code'] }}</div>
                <div>{{ $issuer['address'] }}</div>
                <div>TEL: {{ $issuer['tel'] }}  FAX: {{ $issuer['fax'] }}</div>
            </div>
            <div class="settlement-info">
                <table>
                    <tr>
                        <td style="width: 40%;">精算番号:</td>
                        <td style="width: 60%;">{{ $settlement->settlement_number }}</td>
                    </tr>
                    <tr>
                        <td>発行日:</td>
                        <td>{{ $settlement->created_at->format('Y年n月j日') }}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="client-section">
            <div class="client-info">
                <h3>【委託先】</h3>
                <div><strong>{{ $clientData['client_name'] }}  様</strong></div>
                <div>{{ $clientData['postal_code'] }}</div>
                <div>{{ $clientData['address'] }}</div>
            </div>
            <div class="payment-box">
                <h3>【お支払金額】</h3>
                <div class="payment-amount">¥{{ number_format($paymentAmount, 0) }}</div>
            </div>
        </div>
        
        <div class="period-section">
            【精算期間】  {{ $settlement->billing_start_date->format('Y年n月j日') }} 〜 {{ $settlement->billing_end_date->format('Y年n月j日') }}
        </div>
        
        <table class="details-table">
            <thead>
                <tr>
                    <th style="width: 15%;">商品コード</th>
                    <th style="width: 35%;">商品名</th>
                    <th style="width: 15%;">単価</th>
                    <th style="width: 10%;">販売数</th>
                    <th style="width: 20%;">売上金額</th>
                </tr>
            </thead>
            <tbody>
                @if (!empty($clientData['sales_details']))
                    @foreach ($clientData['sales_details'] as $sale)
                    <tr>
                        <td class="text-center">{{ $sale['product_code'] ?? '' }}</td>
                        <td>{{ $sale['product_name'] ?? '' }}</td>
                        <td class="text-right">{{ number_format((float) ($sale['unit_price'] ?? 0), 0) }}</td>
                        <td class="text-center">{{ (int) ($sale['quantity'] ?? 0) }}</td>
                        <td class="text-right">{{ number_format((float) ($sale['amount'] ?? 0), 0) }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="5" class="text-center">売上明細がありません</td>
                    </tr>
                @endif
            </tbody>
        </table>
        
        <div class="calculation-section">
            <table class="calculation-table">
                <tr class="subtotal-row">
                    <td style="width: 40%;"></td>
                    <td style="width: 40%; text-align: right;"><strong>小計</strong></td>
                    <td>{{ number_format($salesTotal, 0) }}</td>
                </tr>
                <tr>
                    <td>委託販売手数料({{ (int) ($commissionRate * 100) }}%)</td>
                    <td></td>
                    <td>{{ number_format(-$commission, 0) }}</td>
                </tr>
                <tr>
                    <td>消費税({{ (int) ($taxRate * 100) }}%)</td>
                    <td></td>
                    <td>{{ number_format($tax, 0) }}</td>
                </tr>
                <tr>
                    <td>振込手数料</td>
                    <td></td>
                    <td>{{ number_format(-$transferFee, 0) }}</td>
                </tr>
                <tr class="payment-row">
                    <td></td>
                    <td style="text-align: right;"><strong>お支払金額</strong></td>
                    <td><strong>{{ number_format($paymentAmount, 0) }}</strong></td>
                </tr>
            </table>
        </div>
        
        <div class="bank-info-section">
            <h4>【お振込予定日】  {{ $settlement->payment_date->format('Y年n月j日') }}</h4>
            
            <h4>【振込先】</h4>
            <div>{{ $clientData['bank_name'] }}  {{ $clientData['branch_name'] }}  {{ $clientData['account_type'] }}  {{ $clientData['account_number'] }}</div>
            <div>口座名義: {{ $clientData['account_name'] ?? '' }}</div>
        </div>
        
        <div class="note">
            ※ご不明な点がございましたら、上記連絡先までお問い合わせください。
        </div>
    </div>
    @endforeach
</body>
</html>
