<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>全銀フォーマット変換</title>
    <!-- TailwindCSS を使ってスタイリング -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- ヘッダー -->
            <div>
                <h1 class="text-center text-3xl font-extrabold text-gray-900">
                    全銀フォーマット変換
                </h1>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Excel ファイルをアップロードして、固定長テキストに変換します
                </p>
            </div>

            <!-- 成功メッセージの表示 -->
            @if (isset($message))
                <div class="rounded-md bg-green-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">
                                {{ $message }}
                            </p>
                            @if (isset($filename))
                                <p class="text-sm text-green-700 mt-1">
                                    アップロードファイル: {{ $filename }}
                                </p>
                            @endif
                            @if (isset($recordCount))
                                <p class="text-sm text-green-700 mt-1">
                                    変換レコード数: {{ $recordCount }}件
                                </p>
                            @endif
                            @if (isset($downloadFilename))
                                <div class="mt-3">
                                    <a href="{{ route('zengin.download', ['filename' => $downloadFilename]) }}"
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                        <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        変換ファイルをダウンロード
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- エラーメッセージの表示 -->
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4">
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
                                <ul class="list-disc pl-5 space-y-1">
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
            <div class="mt-8 bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                <form action="{{ route('zengin.preview') }}" method="POST" enctype="multipart/form-data"
                    class="space-y-6" id="uploadForm">
                    @csrf

                    <div>
                        <label for="excel_file" class="block text-sm font-medium text-gray-700">
                            Excel ファイル
                        </label>
                        <div class="mt-1">
                            <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls,.csv"
                                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none focus:border-indigo-500"
                                required>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">
                            対応形式: .xlsx, .xls, .csv（最大 10MB）
                        </p>
                        <!-- ファイル情報表示エリア -->
                        <div id="fileInfo" class="mt-2 hidden">
                            <div class="flex items-center text-sm">
                                <svg class="h-4 w-4 mr-1 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span id="fileName" class="text-gray-700"></span>
                                <span id="fileSize" class="ml-2 text-gray-500"></span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <button type="submit" id="submitButton"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-gray-400 disabled:cursor-not-allowed">
                            データを確認する
                        </button>
                    </div>
                </form>
            </div>

            <!-- 説明 -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-900 mb-2">📝 Excel ファイルの形式</h3>
                <p class="text-xs text-blue-800 mb-2">1行目はヘッダー行として、以下の列名を含めてください：</p>
                <div class="text-xs text-blue-800 space-y-2">
                    <div>
                        <p class="font-semibold">必須項目（全銀フォーマット変換用）：</p>
                        <ul class="list-disc list-inside ml-2 space-y-1">
                            <li>金融機関コード（4桁）</li>
                            <li>金融機関名</li>
                            <li>支店コード（3桁）</li>
                            <li>支店名</li>
                            <li>預金種目（普通 / 当座）</li>
                            <li>口座番号（7桁）</li>
                            <li>口座名義（カナ）または 口座名義カナ</li>
                            <li>振込金額</li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-600">その他の項目：</p>
                        <p class="text-gray-600 ml-2">顧客ID、事業者名、代表者氏名など（これらは変換に使用されません）</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for file validation -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('excel_file');
            const fileInfo = document.getElementById('fileInfo');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');
            const submitButton = document.getElementById('submitButton');
            const uploadForm = document.getElementById('uploadForm');

            // 許可される拡張子
            const allowedExtensions = ['xlsx', 'xls', 'csv'];
            const maxFileSize = 10 * 1024 * 1024; // 10MB

            // ファイルサイズを読みやすい形式に変換
            function formatFileSize(bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
                return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
            }

            // ファイル選択時のバリデーション
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];

                if (!file) {
                    fileInfo.classList.add('hidden');
                    submitButton.disabled = false;
                    return;
                }

                // ファイル名から拡張子を取得
                const fileExtension = file.name.split('.').pop().toLowerCase();

                // 拡張子チェック
                if (!allowedExtensions.includes(fileExtension)) {
                    alert('❌ エラー: Excelファイル（.xlsx、.xls、.csv）のみアップロード可能です。\n\n選択されたファイル: ' + file.name);
                    fileInput.value = ''; // ファイル選択をクリア
                    fileInfo.classList.add('hidden');
                    submitButton.disabled = true;
                    return;
                }

                // ファイルサイズチェック
                if (file.size > maxFileSize) {
                    alert('❌ エラー: ファイルサイズが大きすぎます。\n\n最大サイズ: 10MB\n選択されたファイル: ' + formatFileSize(file.size));
                    fileInput.value = ''; // ファイル選択をクリア
                    fileInfo.classList.add('hidden');
                    submitButton.disabled = true;
                    return;
                }

                // 正常なファイル - 情報を表示
                fileName.textContent = file.name;
                fileSize.textContent = '(' + formatFileSize(file.size) + ')';
                fileInfo.classList.remove('hidden');
                submitButton.disabled = false;
            });

            // フォーム送信時の最終確認
            uploadForm.addEventListener('submit', function(e) {
                const file = fileInput.files[0];

                if (!file) {
                    e.preventDefault();
                    alert('⚠️ ファイルを選択してください。');
                    return false;
                }

                // ダブルクリック防止
                submitButton.disabled = true;
                submitButton.textContent = '読み込み中...';

                return true;
            });
        });
    </script>
</body>

</html>
