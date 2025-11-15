<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>全銀フォーマット変換 - アップロード</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
    <!-- ヘッダーナビゲーション -->
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

    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-extrabold text-gray-900">
                    全銀フォーマット変換
                </h1>
                <p class="mt-2 text-sm text-gray-600">
                    Excelファイルをアップロードして全銀フォーマット（120バイト固定長）に変換
                </p>
            </div>

            <!-- エラー表示 -->
            @if ($errors->any())
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
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">
                                エラーが発生しました
                            </h3>
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
                    <form action="{{ route('zengin.preview') }}" method="POST" enctype="multipart/form-data"
                        id="uploadForm">
                        @csrf

                        <div class="space-y-6">
                            <!-- ファイル選択 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Excelファイルを選択
                                </label>
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
                                            <p class="mb-2 text-sm text-gray-500">
                                                <span class="font-semibold">クリックしてファイルを選択</span>
                                                またはドラッグ&ドロップ
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                Excel (XLSX, XLS, CSV) / 最大10MB
                                            </p>
                                            <p id="fileName" class="mt-4 text-sm text-indigo-600 font-medium"></p>
                                            <p id="fileSize" class="text-xs text-gray-500"></p>
                                        </div>
                                        <input id="excel_file" name="excel_file" type="file" class="hidden"
                                            accept=".xlsx,.xls,.csv" required />
                                    </label>
                                </div>
                            </div>

                            <!-- アップロードボタン -->
                            <div>
                                <button type="submit" id="submitBtn"
                                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-gray-400 disabled:cursor-not-allowed transition">
                                    <svg class="mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <span id="btnText">プレビュー</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 使い方 -->
            <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-900 mb-2">📘 使い方</h3>
                <ol class="text-sm text-blue-800 space-y-1 list-decimal list-inside">
                    <li>顧客情報が入力されたExcelファイルを選択</li>
                    <li>「プレビュー」ボタンをクリック</li>
                    <li>データを確認後、「全銀フォーマットに変換」ボタンをクリック</li>
                    <li>変換されたファイル（.txt）がダウンロードされます</li>
                </ol>
            </div>
        </div>
    </div>

    <script>
        // ファイル選択時の処理
        document.getElementById('excel_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const submitBtn = document.getElementById('submitBtn');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');

            if (file) {
                // ファイル名表示
                fileName.textContent = file.name;

                // ファイルサイズ表示
                const size = file.size;
                const sizeText = size < 1024 * 1024 ?
                    (size / 1024).toFixed(2) + ' KB' :
                    (size / (1024 * 1024)).toFixed(2) + ' MB';
                fileSize.textContent = sizeText;

                // 許可された拡張子チェック
                const allowedExtensions = ['.xlsx', '.xls', '.csv'];
                const fileExtension = file.name.substring(file.name.lastIndexOf('.')).toLowerCase();

                if (!allowedExtensions.includes(fileExtension)) {
                    alert('Excelファイル（xlsx, xls, csv）を選択してください。');
                    e.target.value = '';
                    fileName.textContent = '';
                    fileSize.textContent = '';
                    submitBtn.disabled = true;
                    return;
                }

                submitBtn.disabled = false;
            }
        });

        // フォーム送信時
        document.getElementById('uploadForm').addEventListener('submit', function() {
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            submitBtn.disabled = true;
            btnText.textContent = '読み込み中...';
        });
    </script>
</body>

</html>

