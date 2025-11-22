<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>ご予約ありがとうございます</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .wrapper {
            background-color: #f7fafc;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }

        .header {
            background-color: #4a5568;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }

        .content {
            padding: 30px;
        }

        .content h1 {
            font-size: 24px;
            color: #2d3748;
            margin-top: 0;
        }

        .content p {
            margin-bottom: 20px;
        }

        .details {
            border-top: 1px solid #e2e8f0;
            margin-top: 20px;
            padding-top: 20px;
        }

        .details ul {
            list-style: none;
            padding: 0;
        }

        .details li {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .details li:last-child {
            border-bottom: none;
        }

        .details strong {
            color: #4a5568;
        }

        .footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #718096;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h2>ご予約ありがとうございます</h2>
            </div>
            <div class="content">
                <h1>ご予約内容の確認</h1>
                <p><strong>{{ $reservation->customer_name }} 様</strong></p>
                <p>この度はご予約いただき、誠にありがとうございます。以下の内容でご予約を承りましたので、ご確認ください。</p>

                <div class="details">
                    <ul>
                        <li>
                            <span>プログラム名</span>
                            <strong>{{ $reservation->experienceProgram?->name ?? 'N/A' }}</strong>
                        </li>
                        <li>
                            <span>ご予約日時</span>
                            <strong>{{ $reservation->reservation_date?->format('Y年n月j日') }}
                                {{ substr($reservation->reservation_time, 0, 5) }}</strong>
                        </li>
                        <li>
                            <span>ご参加人数</span>
                            <strong>{{ $reservation->participant_count }} 名</strong>
                        </li>
                    </ul>
                </div>

                <p style="margin-top: 30px;">スタッフ一同、お会いできるのを楽しみにしております。<br>当日はお気をつけてお越しくださいませ。</p>
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} 予約システム. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>

</html>
