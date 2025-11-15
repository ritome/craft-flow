<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>レジデータアップロード - CraftFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50">
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <!-- ヘッダー -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">
                    📊 レジデータ自動集計システム
                </h1>
                <p class="mt-2 text-gray-600">
                    POSレジPDFファイルをアップロードして、Excelで集計結果をダウンロードできます
                </p>
            </div>

            <!-- メインカード -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="px-6 py-8">
                    <!-- エラーメッセージ -->
                    @if ($errors->any())
                        <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">エラーが発生しました</h3>
                                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- アップロードフォーム -->
                    <form action="{{ route('pdf.import') }}" method="POST" enctype="multipart/form-data"
                        id="uploadForm">
                        @csrf

                        <!-- ファイル選択エリア -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                PDFファイルを選択（最大4ファイル）
                            </label>
                            <div
                                class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-400 transition-colors">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                                        viewBox="0 0 48 48">
                                        <path
                                            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="pdf_files"
                                            class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                            <span>ファイルを選択</span>
                                            <input id="pdf_files" name="pdf_files[]" type="file" class="sr-only"
                                                multiple accept=".pdf" required onchange="displayFileNames()">
                                        </label>
                                        <p class="pl-1">またはドラッグ&ドロップ</p>
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        PDF形式、最大10MB/ファイル
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- 選択されたファイルリスト -->
                        <div id="fileList" class="mb-6 hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                選択されたファイル
                            </label>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <ul id="fileListItems" class="space-y-2">
                                    <!-- JavaScriptで動的に追加 -->
                                </ul>
                            </div>
                        </div>

                        <!-- 説明 -->
                        <div class="mb-6 bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">使い方</h3>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <ol class="list-decimal list-inside space-y-1">
                                            <li>4台のPOSレジから出力されたPDFファイルを選択してください</li>
                                            <li>「集計してダウンロード」ボタンをクリック</li>
                                            <li>自動的にExcelファイルがダウンロードされます</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- アップロードボタン -->
                        <div class="flex items-center justify-between">
                            <a href="{{ route('pdf.history') }}" class="text-sm text-blue-600 hover:text-blue-500">
                                📋 履歴を見る
                            </a>
                            <button type="submit" id="submitBtn"
                                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="mr-2 -ml-1 h-5 w-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <span id="submitBtnText">集計してダウンロード</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- フッター情報 -->
            <div class="mt-8 text-center text-sm text-gray-500">
                <p>CraftFlow - レジデータ自動集計システム v1.0.0</p>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // ファイル選択時の表示
        function displayFileNames() {
            const input = document.getElementById('pdf_files');
            const fileList = document.getElementById('fileList');
            const fileListItems = document.getElementById('fileListItems');

            if (input.files.length > 0) {
                fileList.classList.remove('hidden');
                fileListItems.innerHTML = '';

                Array.from(input.files).forEach((file, index) => {
                    const li = document.createElement('li');
                    li.className = 'flex items-center text-sm text-gray-700';
                    li.innerHTML = `
                        <svg class="h-5 w-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span>${file.name}</span>
                        <span class="ml-2 text-gray-500">(${(file.size / 1024 / 1024).toFixed(2)} MB)</span>
                    `;
                    fileListItems.appendChild(li);
                });
            } else {
                fileList.classList.add('hidden');
            }
        }

        // フォーム送信時の処理
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const submitBtnText = document.getElementById('submitBtnText');

            // ボタンを無効化
            submitBtn.disabled = true;
            submitBtnText.textContent = '処理中...';

            // スピナーを追加
            submitBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>処理中...</span>
            `;
        });

        // ドラッグ&ドロップ対応
        const dropZone = document.querySelector('.border-dashed');

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('border-blue-500', 'bg-blue-50');
        });

        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-blue-500', 'bg-blue-50');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-blue-500', 'bg-blue-50');

            const input = document.getElementById('pdf_files');
            input.files = e.dataTransfer.files;
            displayFileNames();
        });
    </script>
</body>

</html>
