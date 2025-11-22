<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>全銀フォーマット変換 - アップロード</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
    <div class="bg-white border-b border-gray-200 px-4 py-3 mb-6 shadow-sm">
        <div class="max-w-7xl mx-auto">
            <a href="{{ route('portal') }}"
                class="inline-flex items-center text-indigo-600 hover:text-indigo-800 font-bold transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 mr-1">
                    <path fill-rule="evenodd"
                        d="M9.293 2.293a1 1 0 011.414 0l7 7A1 1 0 0117 11h-1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1H9a1 1 0 00-1 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-6H3a1 1 0 01-.707-1.707l7-7z"
                        clip-rule="evenodd" />
                </svg>
                社内業務システム（トップ）に戻る
            </a>
        </div>
    </div>
    <!-- ナビゲーション -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex space-x-8">
                    <a href="{{ route('zengin.upload') }}"
                        class="inline-flex items-center px-1 pt-1 border-b-2 border-indigo-500 text-sm font-medium text-gray-900">
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

    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <!-- ヘッダー -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">全銀フォーマット変換</h1>
            <p class="mt-2 text-sm text-gray-600">
                Excelファイルをアップロードして、全銀フォーマット（120バイト固定長、Shift-JIS、CRLF）のテキストファイルに変換します。
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
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    Excelファイルを選択
                </h3>

                <form action="{{ route('zengin.preview') }}" method="POST" enctype="multipart/form-data"
                    id="uploadForm">
                    @csrf

                    <div class="flex items-center justify-center w-full">
                        <label for="excel_file"
                            class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <svg class="w-10 h-10 mb-3 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                    </path>
                                </svg>
                                <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">クリックしてファイルを選択</span>
                                    またはドラッグ＆ドロップ</p>
                                <p class="text-xs text-gray-500">Excel形式（XLSX, XLS）またはCSV（最大10MB）</p>
                                <p class="mt-2 text-xs text-gray-400" id="fileName"></p>
                            </div>
                            <input id="excel_file" name="excel_file" type="file" accept=".xlsx,.xls,.csv"
                                class="hidden" />
                        </label>
                    </div>

                    <div class="mt-6">
                        <button type="submit" id="submitBtn"
                            class="w-full inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-gray-400 disabled:cursor-not-allowed transition"
                            disabled>
                            <svg class="mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            プレビューを表示
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 必要なデータ項目の説明 -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h4 class="text-sm font-semibold text-blue-900 mb-3">📋 必要なデータ項目</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-800">
                <div>
                    <ul class="space-y-1">
                        <li>• 金融機関コード（4桁）</li>
                        <li>• 金融機関名</li>
                        <li>• 支店コード（3桁）</li>
                        <li>• 支店名</li>
                    </ul>
                </div>
                <div>
                    <ul class="space-y-1">
                        <li>• 預金種目（1=普通、2=当座）</li>
                        <li>• 口座番号（7桁以内）</li>
                        <li>• 口座名義（カナ）</li>
                        <li>• 振込金額</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('excel_file');
        const fileName = document.getElementById('fileName');
        const submitBtn = document.getElementById('submitBtn');

        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                fileName.textContent = `選択されたファイル: ${file.name}`;
                submitBtn.disabled = false;
            } else {
                fileName.textContent = '';
                submitBtn.disabled = true;
            }
        });
    </script>
</body>

</html>
