<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>委託精算書一括発行 - アップロード</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
    <!-- ナビゲーション -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex space-x-8">
                    <a href="{{ route('settlements.index') }}"
                        class="inline-flex items-center px-1 pt-1 border-b-2 border-indigo-500 text-sm font-medium text-gray-900">
                        📤 精算書生成
                    </a>
                    <a href="{{ route('settlements.history') }}"
                        class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 transition">
                        📋 履歴
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <!-- ヘッダー -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">委託精算書一括発行</h1>
            <p class="mt-2 text-sm text-gray-600">
                顧客マスタと売上データをアップロードして、委託先ごとの精算書（Excel/PDF）を一括生成します。
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-md bg-red-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">エラーが発生しました</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- アップロードフォーム -->
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <form action="{{ route('settlements.generate') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <!-- 請求期間 -->
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">請求期間</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="billing_start_date" class="block text-sm font-medium text-gray-700 mb-2">
                                    請求開始日 <span class="text-red-500">*</span>
                                </label>
                                <input type="date" name="billing_start_date" id="billing_start_date"
                                    value="{{ old('billing_start_date') }}" required
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label for="billing_end_date" class="block text-sm font-medium text-gray-700 mb-2">
                                    請求終了日 <span class="text-red-500">*</span>
                                </label>
                                <input type="date" name="billing_end_date" id="billing_end_date"
                                    value="{{ old('billing_end_date') }}" required
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>

                    <!-- ファイルアップロード -->
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">データファイル</h3>

                        <!-- 顧客マスタ -->
                        <div class="mb-4">
                            <label for="customer_file" class="block text-sm font-medium text-gray-700 mb-2">
                                顧客マスタ <span class="text-red-500">*</span>
                            </label>
                            <input type="file" name="customer_file" id="customer_file" accept=".xlsx,.xls,.csv"
                                required
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            <p class="mt-1 text-xs text-gray-500">Excel（.xlsx, .xls）またはCSV形式（最大10MB）</p>
                        </div>

                        <!-- 売上データ -->
                        <div>
                            <label for="sales_file" class="block text-sm font-medium text-gray-700 mb-2">
                                売上データ <span class="text-red-500">*</span>
                            </label>
                            <input type="file" name="sales_file" id="sales_file" accept=".xlsx,.xls,.csv" required
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            <p class="mt-1 text-xs text-gray-500">Excel（.xlsx, .xls）またはCSV形式（最大10MB）</p>
                        </div>
                    </div>

                    <!-- 送信ボタン -->
                    <div class="mt-6">
                        <button type="submit"
                            class="w-full inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                            <svg class="mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            精算書を生成する
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 必要なデータ項目の説明 -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h4 class="text-sm font-semibold text-blue-900 mb-3">📋 必要なデータ項目</h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-blue-800">
                <!-- 顧客マスタ -->
                <div>
                    <h5 class="font-semibold mb-2">顧客マスタ</h5>
                    <ul class="space-y-1">
                        <li>• client_code（委託先コード）</li>
                        <li>• client_name（委託先名）</li>
                        <li>• postal_code（郵便番号）</li>
                        <li>• address（住所）</li>
                        <li>• bank_name（銀行名）</li>
                        <li>• branch_name（支店名）</li>
                        <li>• account_type（口座種別）</li>
                        <li>• account_number（口座番号）</li>
                        <li>• account_name（口座名義）</li>
                    </ul>
                </div>

                <!-- 売上データ -->
                <div>
                    <h5 class="font-semibold mb-2">売上データ</h5>
                    <ul class="space-y-1">
                        <li>• sale_date（売上日）</li>
                        <li>• client_code（委託先コード）</li>
                        <li>• product_name（商品名）</li>
                        <li>• unit_price（単価）</li>
                        <li>• quantity（数量）</li>
                        <li>• amount（売上金額）</li>
                        <li>• commission_rate（手数料率）</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>

</html>



