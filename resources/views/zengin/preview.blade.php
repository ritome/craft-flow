<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>データ確認 - 全銀フォーマット変換</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
    <!-- ヘッダーナビゲーション -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex space-x-8">
                    <a href="{{ route('zengin.upload') }}"
                        class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 transition">
                        📤 アップロード
                    </a>
                    <a href="{{ route('zengin.history') }}"
                        class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 transition">
                        📋 履歴
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <!-- ヘッダー -->
            <div class="mb-8">
                <h1 class="text-3xl font-extrabold text-gray-900">
                    データ確認
                </h1>
                <p class="mt-2 text-sm text-gray-600">
                    Excelから読み込んだデータを確認してください
                </p>
            </div>

            <!-- サマリー -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-4 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">
                            ファイル名
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 break-all">
                            {{ $filename }}
                        </dd>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">
                            有効レコード数
                        </dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">
                            {{ number_format($totalCount) }}
                        </dd>
                    </div>
                </div>

                @if (isset($skippedCount) && $skippedCount > 0)
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                スキップ行数
                            </dt>
                            <dd class="mt-1 text-3xl font-semibold text-yellow-600">
                                {{ number_format($skippedCount) }}
                            </dd>
                        </div>
                    </div>
                @endif

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">
                            エラー件数
                        </dt>
                        <dd class="mt-1 text-3xl font-semibold {{ $errorCount > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ number_format($errorCount) }}
                        </dd>
                    </div>
                </div>
            </div>

            <!-- 空白行スキップ情報 -->
            @if (isset($skippedCount) && $skippedCount > 0)
                <div class="rounded-md bg-yellow-50 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">
                                {{ $skippedCount }}行の空白行をスキップしました
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>主要フィールド（金融機関コード、支店コード、口座番号、口座名義、金額）がすべて空白の行は自動的にスキップされています。</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- エラーアラート -->
            @if ($errorCount > 0)
                <div class="rounded-md bg-red-50 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-sm font-medium text-red-800">
                                {{ $errorCount }}件のエラーが検出されました
                            </h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p class="mb-2">以下のエラーを修正してから再度アップロードしてください。</p>
                                @if (isset($allErrors) && count($allErrors) > 0)
                                    <div class="mt-3 max-h-60 overflow-y-auto border border-red-200 rounded p-3 bg-white">
                                        <ul class="space-y-1 text-xs">
                                            @foreach (array_slice($allErrors, 0, 50) as $error)
                                                <li class="flex items-start">
                                                    <span class="font-semibold text-red-600 mr-2">行{{ $error['line'] ?? '不明' }}:</span>
                                                    <span class="text-gray-700">{{ $error['message'] ?? 'エラー詳細不明' }}</span>
                                                </li>
                                            @endforeach
                                            @if (count($allErrors) > 50)
                                                <li class="text-gray-500 italic mt-2">
                                                    ...他{{ count($allErrors) - 50 }}件のエラー
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- データテーブル -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        プレビューデータ（最大{{ count($previewData) }}件表示）
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    行番号
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    金融機関
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    支店
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    預金種目
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    口座番号
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    口座名義
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    金額
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    状態
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($previewData as $row)
                                <tr class="{{ $row['_has_error'] ? 'bg-red-50' : 'hover:bg-gray-50' }} transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $row['_line_number'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $row['金融機関名'] ?? $row['bank_name'] ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $row['支店名'] ?? $row['branch_name'] ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $row['預金種目'] ?? $row['account_type'] ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $row['口座番号'] ?? $row['account_number'] ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $row['口座名義（カナ）'] ?? $row['account_holder'] ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                        ¥{{ number_format($row['振込金額'] ?? $row['amount'] ?? 0) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if ($row['_has_error'])
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                エラー
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                OK
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @if ($row['_has_error'])
                                    <tr class="bg-red-50">
                                        <td colspan="8" class="px-6 py-2">
                                            <div class="text-xs text-red-700">
                                                ❌ {{ $row['_error_message'] }}
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- アクションボタン -->
            <div class="flex items-center justify-between">
                <a href="{{ route('zengin.upload') }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                    <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    最初に戻る
                </a>

                @if ($errorCount === 0)
                    <form action="{{ route('zengin.convert') }}" method="POST" id="convertForm">
                        @csrf
                        <button type="submit" id="convertBtn"
                            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-gray-400 disabled:cursor-not-allowed transition">
                            <svg class="mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span id="convertBtnText">全銀フォーマットに変換</span>
                        </button>
                    </form>
                @else
                    <button disabled
                        class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-gray-400 cursor-not-allowed">
                        <svg class="mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        エラーあり（変換不可）
                    </button>
                @endif
            </div>
        </div>
    </div>

    <script>
        @if ($errorCount === 0)
            document.addEventListener('DOMContentLoaded', function() {
                console.log('=== プレビュー画面読み込み完了 ===');
                console.log('errorCount:', {{ $errorCount }});
                console.log('totalCount:', {{ $totalCount }});
                
                const form = document.getElementById('convertForm');
                const btn = document.getElementById('convertBtn');
                
                console.log('フォーム要素:', form);
                console.log('ボタン要素:', btn);
                
                if (!form || !btn) {
                    console.error('❌ フォームまたはボタンが見つかりません');
                    return;
                }
                
                // ボタンクリック時
                btn.addEventListener('click', function(e) {
                    console.log('✅ ボタンがクリックされました');
                    console.log('ボタンのtype:', btn.type);
                    console.log('ボタンのdisabled:', btn.disabled);
                });
                
                // フォーム送信時
                form.addEventListener('submit', function(e) {
                    console.log('✅ フォーム送信開始');
                    console.log('アクション:', form.action);
                    console.log('メソッド:', form.method);
                    
                    // ボタンを無効化
                    btn.disabled = true;
                    document.getElementById('convertBtnText').textContent = '変換中...';
                    
                    console.log('ボタン無効化完了、送信を続行します');
                    // フォーム送信を継続（e.preventDefault()は呼ばない）
                });
            });
        @else
            console.log('エラーがあるため変換ボタンは無効です');
            console.log('errorCount:', {{ $errorCount }});
        @endif
    </script>
</body>

</html>

