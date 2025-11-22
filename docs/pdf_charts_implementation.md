# PDF集計履歴 売上グラフ機能実装ドキュメント

## 概要
PDF集計履歴画面に日別・月別の売上グラフ表示機能を追加しました。

## 実装日
2025年11月22日

## 実装内容

### 1. Voltコンポーネント作成
**ファイル**: `resources/views/livewire/pdf/charts.blade.php`

#### 主な機能
- グラフの表示/非表示切り替え
- 日別・月別グラフの切り替え
- 期間選択（今月、先月、直近3ヶ月、直近6ヶ月、今年）
- Chart.jsによる棒グラフ表示
- 売上データサマリー表示（合計、平均、件数）

#### 状態管理
```php
state([
    'chartType' => 'daily',      // 'daily' or 'monthly'
    'period' => 'this_month',    // プリセット期間
    'chartData' => null,         // グラフデータ
    'showChart' => false,        // 表示/非表示
    'periods' => [...]           // 期間プリセット
]);
```

#### 主要メソッド
- `toggleChart()`: グラフの表示/非表示切り替え
- `changePeriod($newPeriod)`: 期間変更
- `changeChartType($newType)`: グラフタイプ変更
- `loadChartData()`: グラフデータ読み込み
- `getDailySalesData($startDate, $endDate)`: 日別売上データ取得
- `getMonthlySalesData($startDate, $endDate)`: 月別売上データ取得

### 2. 履歴画面への統合
**ファイル**: `resources/views/history.blade.php`

#### 追加内容
- Chart.js CDN読み込み
- Livewire スタイル・スクリプト読み込み
- グラフコンポーネントの埋め込み

```html
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>

<!-- Voltコンポーネント埋め込み -->
@livewire('pdf.charts')
```

### 3. ファクトリー作成
**ファイル**: `database/factories/ImportHistoryFactory.php`

テスト用のダミーデータ生成機能を追加。

### 4. テスト実装
**ファイル**: `tests/Feature/Pdf/ChartsComponentTest.php`

#### テストケース
1. グラフコンポーネントがレンダリングされる
2. 初期状態ではグラフが非表示
3. トグルボタンでグラフの表示/非表示が切り替わる
4. 期間の変更が正しく機能する
5. グラフタイプの変更が正しく機能する
6. 日別売上データが正しく集計される
7. 月別売上データが正しく集計される
8. 履歴画面でグラフコンポーネントが表示される

**テスト結果**: 全8テスト成功 ✅

## 使用技術

### フロントエンド
- **Chart.js v4.4.0**: グラフ描画ライブラリ
- **Alpine.js**: Livewireに同梱、リアクティブUI制御
- **Tailwind CSS**: スタイリング

### バックエンド
- **Livewire Volt (Functional)**: コンポーネント実装
- **Laravel Eloquent**: データベースクエリ
- **Carbon**: 日付操作

## 機能詳細

### グラフタイプ
1. **日別グラフ**: 選択期間内の日ごとの売上を表示
2. **月別グラフ**: 選択期間内の月ごとの売上を表示

### 期間プリセット
- 今月
- 先月
- 直近3ヶ月
- 直近6ヶ月
- 今年

### データサマリー
- 合計売上
- 平均売上
- データ件数

## UI/UX

### グラフ表示
- 棒グラフ（Bar Chart）形式
- レスポンシブデザイン対応
- ホバー時にツールチップ表示
- 金額フォーマット（¥マーク、カンマ区切り）

### コントロール
- グラフタイプボタン（日別/月別）
- 期間選択ボタン（5つのプリセット）
- 表示/非表示トグルボタン

### カラースキーム
- メインカラー: Indigo（#4F46E5）
- データサマリー: Blue, Green, Purple
- ホバー効果: 透過度60%

## パフォーマンス考慮事項

1. **データ取得**: 期間を指定したクエリで必要なデータのみ取得
2. **グラフ描画**: Alpine.jsの`x-init`でチャート初期化
3. **再描画**: データ変更時に既存チャートを破棄してから新規作成
4. **キャッシュ**: Livewireの状態管理により不要な再取得を防止

## 今後の拡張可能性

1. **エクスポート機能**: グラフ画像のダウンロード
2. **カスタム期間**: 日付範囲の自由選択
3. **グラフ種類追加**: 折れ線グラフ、円グラフなど
4. **比較機能**: 前年同期比較など
5. **フィルター機能**: 特定レジのデータのみ表示
6. **統計情報**: 最大値、最小値、標準偏差など

## トラブルシューティング

### グラフが表示されない
1. Chart.js CDNが正しく読み込まれているか確認
2. ブラウザコンソールでJavaScriptエラーを確認
3. データが存在するか確認

### データが正しく表示されない
1. `import_histories`テーブルにデータが存在するか確認
2. 期間指定が正しいか確認
3. `total_sales`カラムにデータが入っているか確認

## 関連ファイル

- `resources/views/livewire/pdf/charts.blade.php` - Voltコンポーネント
- `resources/views/history.blade.php` - 履歴画面
- `database/factories/ImportHistoryFactory.php` - テストデータファクトリー
- `tests/Feature/Pdf/ChartsComponentTest.php` - テストファイル
- `app/Models/ImportHistory.php` - モデル

## 参考資料

- [Chart.js Documentation](https://www.chartjs.org/docs/latest/)
- [Livewire Volt Documentation](https://livewire.laravel.com/docs/volt)
- [Alpine.js Documentation](https://alpinejs.dev/)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)

