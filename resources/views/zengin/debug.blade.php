<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>デバッグ情報</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    デバッグ情報
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    システムの状態を確認します
                </p>
            </div>
            
            <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
                <!-- セッション情報 -->
                <div class="mb-6">
                    <h4 class="text-md font-semibold text-gray-800 mb-2">📦 セッション情報</h4>
                    <div class="bg-gray-100 p-4 rounded font-mono text-xs overflow-auto">
                        <pre>{{ json_encode(session()->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
                
                <!-- データベース接続 -->
                <div class="mb-6">
                    <h4 class="text-md font-semibold text-gray-800 mb-2">🗄️ データベース接続</h4>
                    <div class="bg-gray-100 p-4 rounded">
                        @php
                            try {
                                \DB::connection()->getPdo();
                                echo '<span class="text-green-600 font-semibold">✅ 接続成功</span>';
                            } catch (\Exception $e) {
                                echo '<span class="text-red-600 font-semibold">❌ 接続失敗: ' . $e->getMessage() . '</span>';
                            }
                        @endphp
                    </div>
                </div>
                
                <!-- ZenginLog テーブル -->
                <div class="mb-6">
                    <h4 class="text-md font-semibold text-gray-800 mb-2">📋 ZenginLog テーブル</h4>
                    <div class="bg-gray-100 p-4 rounded">
                        @php
                            try {
                                $count = \App\Models\ZenginLog::count();
                                echo '<span class="text-green-600 font-semibold">✅ テーブル存在: ' . $count . '件のレコード</span>';
                            } catch (\Exception $e) {
                                echo '<span class="text-red-600 font-semibold">❌ エラー: ' . $e->getMessage() . '</span>';
                            }
                        @endphp
                    </div>
                </div>
                
                <!-- ストレージパス -->
                <div class="mb-6">
                    <h4 class="text-md font-semibold text-gray-800 mb-2">📁 ストレージパス</h4>
                    <div class="bg-gray-100 p-4 rounded font-mono text-xs">
                        <p><strong>storage_path('app'):</strong> {{ storage_path('app') }}</p>
                        <p><strong>config('zengin.storage_path'):</strong> {{ config('zengin.storage_path') }}</p>
                        <p><strong>フルパス:</strong> {{ storage_path('app/' . config('zengin.storage_path')) }}</p>
                        @php
                            $zenginPath = storage_path('app/' . config('zengin.storage_path'));
                            if (is_dir($zenginPath)) {
                                echo '<p class="text-green-600 mt-2">✅ ディレクトリ存在</p>';
                                $files = \Storage::disk('local')->files(config('zengin.storage_path'));
                                echo '<p class="mt-2"><strong>ファイル数:</strong> ' . count($files) . '</p>';
                            } else {
                                echo '<p class="text-red-600 mt-2">❌ ディレクトリが存在しません</p>';
                            }
                        @endphp
                    </div>
                </div>
                
                <!-- PHP/Laravel情報 -->
                <div class="mb-6">
                    <h4 class="text-md font-semibold text-gray-800 mb-2">⚙️ システム情報</h4>
                    <div class="bg-gray-100 p-4 rounded font-mono text-xs">
                        <p><strong>PHP Version:</strong> {{ PHP_VERSION }}</p>
                        <p><strong>Laravel Version:</strong> {{ app()->version() }}</p>
                        <p><strong>Environment:</strong> {{ app()->environment() }}</p>
                    </div>
                </div>
                
                <!-- アクション -->
                <div class="mt-6 flex space-x-4">
                    <a href="{{ route('zengin.upload') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                        アップロード画面へ
                    </a>
                    
                    <a href="{{ route('zengin.history') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50">
                        履歴画面へ
                    </a>
                    
                    <form action="{{ route('zengin.debug.clear') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-red-300 text-sm font-medium rounded-md shadow-sm text-red-700 bg-white hover:bg-red-50">
                            セッションクリア
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

