<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>委託精算書</title>
    <style>
        body {
            font-family: 'Arial', 'sans-serif';
            font-size: 12px;
            line-height: 1.6;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 20px;
            margin: 0;
        }

        .period {
            text-align: center;
            margin-bottom: 20px;
        }

        .client-section {
            page-break-after: always;
            margin-bottom: 40px;
        }

        .client-info {
            margin-bottom: 20px;
            border: 1px solid #000;
            padding: 10px;
        }

        .client-info table {
            width: 100%;
            border-collapse: collapse;
        }

        .client-info td {
            padding: 5px;
        }

        .client-info td:first-child {
            width: 150px;
            font-weight: bold;
            background-color: #f0f0f0;
        }

        .settlement-summary {
            margin-bottom: 20px;
            border: 1px solid #000;
            padding: 10px;
            background-color: #fffacd;
        }

        .settlement-summary table {
            width: 100%;
            border-collapse: collapse;
        }

        .settlement-summary td {
            padding: 5px;
        }

        .settlement-summary td:first-child {
            width: 150px;
            font-weight: bold;
        }

        .settlement-summary td:last-child {
            text-align: right;
            font-size: 14px;
            font-weight: bold;
        }

        .sales-details {
            margin-top: 20px;
        }

        .sales-details table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }

        .sales-details th,
        .sales-details td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }

        .sales-details th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .sales-details td.amount {
            text-align: right;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>

<body>
    <!-- ヘッダー -->
    <div class="header">
        <h1>委託精算書</h1>
    </div>

    <!-- 請求期間 -->
    <div class="period">
        <strong>請求期間：</strong>
        {{ $settlement->billing_start_date->format('Y年m月d日') }}
        〜
        {{ $settlement->billing_end_date->format('Y年m月d日') }}
    </div>

    <!-- 委託先ごとのページ -->
    @foreach ($settlementData as $clientCode => $data)
        <div class="client-section">
            <!-- 委託先情報 -->
            <div class="client-info">
                <table>
                    <tr>
                        <td>委託先コード</td>
                        <td>{{ $data['client_code'] }}</td>
                    </tr>
                    <tr>
                        <td>委託先名</td>
                        <td>{{ $data['client_name'] }}</td>
                    </tr>
                    <tr>
                        <td>郵便番号</td>
                        <td>〒{{ $data['postal_code'] }}</td>
                    </tr>
                    <tr>
                        <td>住所</td>
                        <td>{{ $data['address'] }}</td>
                    </tr>
                </table>
            </div>

            <!-- 精算サマリー -->
            <div class="settlement-summary">
                <table>
                    <tr>
                        <td>売上金額</td>
                        <td>¥{{ number_format((float) $data['sales_amount']) }}</td>
                    </tr>
                    <tr>
                        <td>手数料</td>
                        <td>¥{{ number_format((float) $data['commission_amount']) }}</td>
                    </tr>
                    <tr style="border-top: 2px solid #000;">
                        <td>支払金額</td>
                        <td>¥{{ number_format((float) $data['payment_amount']) }}</td>
                    </tr>
                </table>
            </div>

            <!-- 銀行情報 -->
            <div class="client-info">
                <table>
                    <tr>
                        <td>銀行名</td>
                        <td>{{ $data['bank_name'] }}</td>
                    </tr>
                    <tr>
                        <td>支店名</td>
                        <td>{{ $data['branch_name'] }}</td>
                    </tr>
                    <tr>
                        <td>口座種別</td>
                        <td>{{ $data['account_type'] }}</td>
                    </tr>
                    <tr>
                        <td>口座番号</td>
                        <td>{{ $data['account_number'] }}</td>
                    </tr>
                    <tr>
                        <td>口座名義</td>
                        <td>{{ $data['account_name'] }}</td>
                    </tr>
                </table>
            </div>

            <!-- 売上明細 -->
            @if (!empty($data['sales_details']))
                <div class="sales-details">
                    <h3>売上明細（{{ $data['sales_count'] }}件）</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>売上日</th>
                                <th>商品名</th>
                                <th>単価</th>
                                <th>数量</th>
                                <th>金額</th>
                                <th>手数料率</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data['sales_details'] as $sale)
                                <tr>
                                    <td>{{ $sale['sale_date'] ?? '' }}</td>
                                    <td>{{ $sale['product_name'] ?? '' }}</td>
                                    <td class="amount">¥{{ number_format((float) ($sale['unit_price'] ?? 0)) }}</td>
                                    <td class="amount">{{ number_format((float) ($sale['quantity'] ?? 0)) }}</td>
                                    <td class="amount">¥{{ number_format((float) ($sale['amount'] ?? 0)) }}</td>
                                    <td class="amount">{{ $sale['commission_rate'] ?? 0 }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="sales-details">
                    <h3>売上件数: {{ $data['sales_count'] }}件</h3>
                    <p style="color: #666; font-style: italic;">※個別の売上明細は記録されていません</p>
                </div>
            @endif
        </div>
    @endforeach

    <!-- フッター -->
    <div class="footer">
        発行日：{{ now()->format('Y年m月d日 H:i') }}
    </div>
</body>

</html>

