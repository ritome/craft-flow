# CraftFlow - プロジェクトドキュメント

**プロジェクト名**: CraftFlow - レジデータ自動集計システム  
**最終更新日**: 2025-11-15  
**バージョン**: 1.0.0

---

## 📚 ドキュメント一覧

このフォルダには、CraftFlowプロジェクトに関する各種仕様書・ドキュメントが含まれています。

### 🎯 システム全体

| ドキュメント | 説明 | 対象読者 |
|------------|------|---------|
| **[system_overview.md](./system_overview.md)** | システム全体の概要設計書。プロジェクトの目的、技術スタック、アーキテクチャ、主要コンポーネントなど | 全員（必読） |

### 📄 入力仕様（PDF）

| ドキュメント | 説明 | 対象読者 |
|------------|------|---------|
| **[pdf_format_specification.md](./pdf_format_specification.md)** | POSレジPDFのフォーマット仕様書。抽出項目、データ構造、正規表現パターンなど | バックエンド開発者 |
| **[parser_implementation_guide.md](./parser_implementation_guide.md)** | PDFパーサーの実装ガイド。コード例、テスト方法、デバッグ方法など | バックエンド開発者 |

### 📊 出力仕様（Excel）

| ドキュメント | 説明 | 対象読者 |
|------------|------|---------|
| **[excel_output_specification.md](./excel_output_specification.md)** | Excel出力の仕様書。シート構成、レイアウト、スタイリング、実装方針など | バックエンド開発者 |

### 🔧 コンポーネント仕様

| ドキュメント | 説明 | 対象読者 |
|------------|------|---------|
| **[PdfReader.md](./PdfReader.md)** | PdfReaderサービスの詳細仕様。PDFからテキストを抽出する機能 | バックエンド開発者 |

---

## 📖 ドキュメントの読み方

### 初めてプロジェクトに参加する場合

1. **[system_overview.md](./system_overview.md)** を読んで、プロジェクト全体を理解する
2. 自分の担当する領域に応じて、以下のドキュメントを読む：
   - バックエンド担当 → PDF仕様、Parser実装ガイド、Excel仕様
   - フロントエンド担当 → system_overview（UI要件部分）

### 機能を実装する場合

#### PDFパース機能を実装する

1. [pdf_format_specification.md](./pdf_format_specification.md) でPDFフォーマットを理解
2. [parser_implementation_guide.md](./parser_implementation_guide.md) で実装方法を確認
3. [PdfReader.md](./PdfReader.md) でPDF読み取り部分を理解

#### Excel出力機能を実装する

1. [excel_output_specification.md](./excel_output_specification.md) で出力仕様を確認
2. サンプルコードを参考に実装

### トラブルシューティング

各ドキュメントの最後に「トラブルシューティング」セクションがあります。問題が発生した場合は、まずそちらを参照してください。

---

## 🚀 クイックスタート

### 開発環境のセットアップ

```bash
# リポジトリをクローン
git clone <repository-url>
cd craft-flow

# 依存パッケージをインストール
composer install
npm install

# 環境変数をコピー
cp .env.example .env

# Sailを起動
./vendor/bin/sail up -d

# マイグレーション実行
./vendor/bin/sail artisan migrate

# poppler-utils をインストール（PDF処理用）
./vendor/bin/sail root-shell -c "apt-get update && apt-get install -y poppler-utils"

# テスト実行
./vendor/bin/sail artisan test
```

詳細は [system_overview.md - 付録C: 開発環境セットアップ](./system_overview.md#c-開発環境セットアップ) を参照。

---

## 🗂️ プロジェクト構成

```
craft-flow/
├── app/
│   ├── Http/Controllers/        # コントローラー
│   ├── Services/                # サービス層（ビジネスロジック）
│   │   ├── PdfImportService.php
│   │   ├── PdfReader.php
│   │   ├── ParserFactory.php
│   │   ├── Normalizer.php
│   │   ├── Aggregator.php
│   │   ├── ExcelExporter.php
│   │   └── Parsers/
│   │       ├── ParserInterface.php
│   │       └── PosRegisterParser.php
│   ├── Models/                  # Eloquentモデル
│   └── Exports/                 # Excel出力クラス
├── docs/                        # ドキュメント（このフォルダ）
│   ├── README.md                # このファイル
│   ├── system_overview.md
│   ├── pdf_format_specification.md
│   ├── excel_output_specification.md
│   ├── parser_implementation_guide.md
│   └── PdfReader.md
├── resources/views/             # Bladeテンプレート
├── routes/web.php               # ルーティング
├── tests/                       # テスト
│   ├── Unit/
│   └── Feature/
└── storage/app/
    ├── pdf_temp/                # PDF一時保存
    └── exports/                 # Excel出力先
```

---

## 📝 ドキュメント作成・更新ルール

### 新規ドキュメント作成時

1. **ファイル名**: スネークケースで `.md` 拡張子（例: `pdf_format_specification.md`）
2. **ヘッダー**: 必ずドキュメント名、バージョン、作成日を記載
3. **目次**: 必ず目次を含める
4. **更新履歴**: 最後に更新履歴テーブルを含める

### ドキュメント更新時

1. **バージョン**: マイナーチェンジは 0.0.1 ずつ、メジャーチェンジは 1.0.0 ずつ上げる
2. **更新履歴**: 必ず更新履歴テーブルに記録
3. **関連ドキュメント**: 関連するドキュメントも同時に更新

### コード変更時のドキュメント更新

- **機能追加**: 該当するドキュメントに追記
- **仕様変更**: ドキュメントを更新し、変更履歴に記録
- **バグ修正**: トラブルシューティングセクションに追記（必要に応じて）

---

## 🔗 外部リンク

### Laravel公式ドキュメント

- [Laravel 12 ドキュメント](https://laravel.com/docs/12.x)
- [Laravel Excel](https://docs.laravel-excel.com/)
- [Livewire](https://livewire.laravel.com/docs)
- [Pest](https://pestphp.com/docs)

### 使用ライブラリ

- [spatie/pdf-to-text](https://github.com/spatie/pdf-to-text)
- [maatwebsite/excel](https://laravel-excel.com/)
- [TailwindCSS 4](https://tailwindcss.com/docs)

---

## ❓ FAQ

### Q: PDFのフォーマットが変わった場合はどうする？

A: 以下の手順で対応：
1. [pdf_format_specification.md](./pdf_format_specification.md) を更新
2. 新しいパーサーを `PosRegisterParserV2` として作成
3. `ParserFactory` で判定ロジックを追加

### Q: 新しい集計項目を追加したい

A: 以下のドキュメントを更新：
1. [excel_output_specification.md](./excel_output_specification.md) - シート構成を更新
2. [system_overview.md](./system_overview.md) - 機能要件を更新

### Q: テストが失敗する

A: 各ドキュメントの「トラブルシューティング」セクションを参照。特に：
- [PdfReader.md - トラブルシューティング](./PdfReader.md#トラブルシューティング)
- [parser_implementation_guide.md - トラブルシューティング](./parser_implementation_guide.md#8-トラブルシューティング)

---

## 🤝 貢献

このプロジェクトに貢献する場合は、以下のガイドラインに従ってください：

1. **コーディング規約**: PSR-12準拠、Laravel Pintで自動整形
2. **テストカバレッジ**: 新機能には必ずテストを追加（70%以上維持）
3. **ドキュメント**: 機能追加・変更時は必ずドキュメントを更新

---

## 📞 連絡先

質問や不明点がある場合は、プロジェクトマネージャーまでお問い合わせください。

---

**Last Updated**: 2025-11-15  
**Document Version**: 1.0.0

---

**END OF DOCUMENT**

