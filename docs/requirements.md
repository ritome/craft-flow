# Requirements Document\n\nプロジェクトの要件定義をここにまとめます。
# 📘 Requirements Document  
盛岡手づくり村システム開発 — 課題3「委託精算書一括発行」

本ドキュメントは、課題3の要件定義・仕様・画面要件・データ構造をまとめ、  
Cursor による開発で **唯一参照すべき仕様書** として運用することを目的とする。

---

# 1. 概要（Overview）

## 1.1 課題
地場産業振興センターでは、委託販売先 約90社に対し、毎月「委託精算書」を作成している。  
現在は複数の Excel を手作業で参照しながら精算書を作成しており、以下の問題がある：

- 作業時間がかかる（毎月25日作成）
- 手作業による入力ミスのリスク
- データ不整合の発生可能性
- 作成物の保管・履歴管理が煩雑

## 1.2 解決したいこと
複数の Excel データから **委託精算書を自動生成・一括ダウンロードできる** Web アプリを作る。

---

# 2. 対象 Issue（担当範囲）

りとめが担当する GitHub Issue：

- #12 精算用Excelデータアップロード機能  
- #13 委託先別精算データ自動変換機能  
- #14 月次委託精算書一括生成機能  
- #15 精算書ファイル（PDF/Excel）ダウンロード機能  
- #16 精算書発行履歴保存機能  
- #17 過去精算書履歴ダウンロード機能  

本ドキュメントはこれら Issue の仕様基準となる。

---

# 3. システムで実現する機能（Scope）

## 3.1 基本機能（Must）

1. **Excel アップロード機能**（顧客マスタ・売上データ）  
2. **委託先ごとの精算データ自動生成**  
3. **精算書 Excel の一括生成**（1委託先＝1シート）  
4. **精算書 PDF の一括生成**（複数委託先を1PDFにまとめる）  
5. **生成ファイルのダウンロード**  
6. **精算履歴の DB 保存**  
7. **精算履歴の一覧表示・再ダウンロード**

## 3.2 拡張可能機能（Should/Could）
- 売上明細のバリデーション（異常値チェック）
- 顧客マスタと売上データの突合エラー表示
- デザイン調整された PDF テンプレート
- 履歴検索（期間検索・委託先検索）

---

# 4. 画面設計

## 4.1 精算トップ画面 `/settlements`

### 入力項目
| 項目名 | 型 | 必須 | 説明 |
|--------|------|--------|-------|
| billing_start_date | date | ○ | 請求開始日 |
| billing_end_date | date | ○ | 請求終了日 |
| customer_file | file (.xlsx/.xls/.csv) | ○ | 顧客マスタ |
| sales_file | file (.xlsx/.xls/.csv) | ○ | 売上データ |

### ボタン
- 精算書を生成する（POST /settlements/generate）

---

## 4.2 精算履歴画面 `/settlements/history`

### 表示項目
- 発行日時
- 請求期間
- 委託先数
- Excel ダウンロードボタン
- PDF ダウンロードボタン

---

# 5. 入力データ定義（Excel 仕様）

## 5.1 顧客マスタ（customer_file）
| 列名 | 型 | 説明 |
|--------|------|--------|
| client_code | string | 委託先コード（キー） |
| client_name | string | 委託先名 |
| postal_code | string | 郵便番号 |
| address | string | 住所 |
| bank_name | string | 銀行名 |
| branch_name | string | 支店名 |
| account_type | string | 普通/当座 |
| account_number | string | 口座番号 |
| account_name | string | 名義 |

## 5.2 売上データ（sales_file）

| 列名 | 型 | 説明 |
|--------|------|--------|
| sale_date | date | 売上日 |
| client_code | string | 委託先コード |
| product_name | string | 商品名 |
| unit_price | number | 単価 |
| quantity | number | 数量 |
| amount | number | 売上金額 |
| commission_rate | number | 手数料率 |

---

# 6. 精算ロジック（ビジネスルール）

### 6.1 集計対象
売上日が  
`billing_start_date <= sale_date <= billing_end_date` にあるもの。

### 6.2 計算式
