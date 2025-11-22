# テストフィクスチャ

このディレクトリには、統合テスト用のサンプル PDF ファイルを配置します。

## 必要なファイル

- `sample.pdf`: 基本的な PDF ファイル
- `japanese_sample.pdf`: 日本語を含む PDF ファイル
- `multipage_sample.pdf`: 複数ページの PDF ファイル
- `register_sample.pdf`: レジから出力される PDF のサンプル
- `large_sample.pdf`: 大きな PDF ファイル（パフォーマンステスト用）
- `corrupted.pdf`: 破損した PDF（エラーハンドリングテスト用）
- `password_protected.pdf`: パスワード保護された PDF（エラーハンドリングテスト用）

## 注意事項

- これらのファイルは Git には含めません（.gitignore に追加済み）
- テストを実行する前に、適切なサンプルファイルを手動で配置してください
- ファイルがない場合、該当するテストは自動的にスキップされます

