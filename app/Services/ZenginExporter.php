<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\SjisPad;

/**
 * 全銀フォーマットエクスポートサービス
 *
 * Excelデータを全銀フォーマット（120バイト固定長、Shift-JIS、CRLF）に変換します
 */
class ZenginExporter
{
    /**
     * 統計情報
     */
    private array $stats = [
        'total_count' => 0,
        'total_amount' => 0,
        'errors' => [],
    ];

    /**
     * 配列データを全銀フォーマット文字列に変換
     *
     * @param  array  $rows  振込データの配列
     * @return string Shift-JIS + CRLF + 120バイト固定長の文字列
     *
     * @throws \Exception バリデーションエラー時
     */
    public function export(array $rows): string
    {
        $lines = [];
        $this->stats = [
            'total_count' => 0,
            'total_amount' => 0,
            'errors' => [],
        ];

        foreach ($rows as $index => $row) {
            $lineNumber = $index + 1;

            try {
                $line = $this->buildLine($row, $lineNumber);
                $lines[] = $line;

                // 統計情報を更新
                $this->stats['total_count']++;
                $this->stats['total_amount'] += (int) ($row['振込金額'] ?? $row['amount'] ?? 0);
            } catch (\Exception $e) {
                $this->stats['errors'][] = [
                    'line' => $lineNumber,
                    'message' => $e->getMessage(),
                ];

                throw $e;
            }
        }

        // CRLF で連結し、末尾にも改行を付加
        $content = implode(config('zengin.newline'), $lines).config('zengin.newline');

        return $content;
    }

    /**
     * 1行分のレコードを生成（120バイト固定長）
     *
     * @param  array  $row  振込データ
     * @param  int  $lineNumber  行番号
     * @return string Shift-JISで120バイトのレコード文字列
     *
     * @throws \Exception バリデーションエラー時
     */
    private function buildLine(array $row, int $lineNumber): string
    {
        // フィールド取得（日本語キーと英語キーの両対応）
        $bankCode = $row['金融機関コード'] ?? $row['bank_code'] ?? '';
        $bankName = $row['金融機関名'] ?? $row['bank_name'] ?? '';
        $branchCode = $row['支店コード'] ?? $row['branch_code'] ?? '';
        $branchName = $row['支店名'] ?? $row['branch_name'] ?? '';
        $accountType = $row['預金種目'] ?? $row['account_type'] ?? '';
        $accountNumber = $row['口座番号'] ?? $row['account_number'] ?? '';
        $recipientName = $row['口座名義（カナ）'] ?? $row['account_holder'] ?? '';
        $amount = $row['振込金額'] ?? $row['amount'] ?? 0;

        // バリデーション
        $this->validateField($bankCode, '金融機関コード', 4, true, $lineNumber);
        $this->validateField($branchCode, '支店コード', 3, true, $lineNumber);
        $this->validateAccountType($accountType, $lineNumber);
        $this->validateAccountNumber($accountNumber, $lineNumber);
        $this->validateAmount($amount, $lineNumber);

        if (empty(trim((string) $recipientName))) {
            throw new \Exception("行番号 {$lineNumber}: 受取人名は必須です");
        }

        // 各フィールドをShift-JISバイト長で整形
        $dataType = '2'; // データ区分（2=振込データ）

        $bankCodePadded = SjisPad::padNumber($bankCode, 4);
        $bankNamePadded = SjisPad::padBytes($bankName, 15);
        $branchCodePadded = SjisPad::padNumber($branchCode, 3);
        $branchNamePadded = SjisPad::padBytes($branchName, 15);

        $dummy1 = str_repeat(' ', 4); // ダミー4バイト

        $accountTypeCode = $this->getAccountTypeCode($accountType);
        $accountNumberPadded = SjisPad::padNumber($accountNumber, 7);
        $recipientNamePadded = SjisPad::padBytes($recipientName, 30);
        $amountPadded = SjisPad::padNumber($amount, 10);

        $dummy2 = str_repeat(' ', 30); // 残りダミー30バイト

        // 1行を結合
        $line = $dataType
            .$bankCodePadded
            .$bankNamePadded
            .$branchCodePadded
            .$branchNamePadded
            .$dummy1
            .$accountTypeCode
            .$accountNumberPadded
            .$recipientNamePadded
            .$amountPadded
            .$dummy2;

        // 120バイトチェック
        $lineLength = strlen($line);
        if ($lineLength !== 120) {
            throw new \Exception("行番号 {$lineNumber}: 行長が120バイトではありません（現在: {$lineLength}バイト）");
        }

        return $line;
    }

    /**
     * フィールドのバリデーション
     */
    private function validateField(mixed $value, string $fieldName, int $length, bool $isNumeric, int $lineNumber): void
    {
        $strValue = trim((string) $value);

        if (empty($strValue)) {
            throw new \Exception("行番号 {$lineNumber}: {$fieldName}は必須です");
        }

        if ($isNumeric && ! is_numeric($strValue)) {
            throw new \Exception("行番号 {$lineNumber}: {$fieldName}は数値である必要があります（現在: {$strValue}）");
        }

        if (mb_strlen($strValue) > $length) {
            throw new \Exception("行番号 {$lineNumber}: {$fieldName}は{$length}桁以内である必要があります（現在: {$strValue}）");
        }
    }

    /**
     * 口座種別のバリデーション
     */
    private function validateAccountType(mixed $value, int $lineNumber): void
    {
        $strValue = trim((string) $value);

        if (! in_array($strValue, ['1', '2', '普通', '当座'], true)) {
            throw new \Exception("行番号 {$lineNumber}: 預金種目は「1」（普通）または「2」（当座）である必要があります（現在: {$strValue}）");
        }
    }

    /**
     * 口座番号のバリデーション
     */
    private function validateAccountNumber(mixed $value, int $lineNumber): void
    {
        $strValue = trim((string) $value);

        if (empty($strValue)) {
            throw new \Exception("行番号 {$lineNumber}: 口座番号は必須です");
        }

        if (! is_numeric($strValue)) {
            throw new \Exception("行番号 {$lineNumber}: 口座番号は数値である必要があります（現在: {$strValue}）");
        }

        if (mb_strlen($strValue) > 7) {
            throw new \Exception("行番号 {$lineNumber}: 口座番号は7桁以内である必要があります（現在: {$strValue}）");
        }
    }

    /**
     * 金額のバリデーション
     */
    private function validateAmount(mixed $value, int $lineNumber): void
    {
        if (! is_numeric($value)) {
            throw new \Exception("行番号 {$lineNumber}: 振込金額は数値である必要があります（現在: {$value}）");
        }

        if ((int) $value <= 0) {
            throw new \Exception("行番号 {$lineNumber}: 振込金額は1円以上である必要があります（現在: {$value}）");
        }

        if (mb_strlen((string) $value) > 10) {
            throw new \Exception("行番号 {$lineNumber}: 振込金額は10桁以内である必要があります（現在: {$value}）");
        }
    }

    /**
     * 口座種別コードを取得
     */
    private function getAccountTypeCode(mixed $type): string
    {
        $strType = trim((string) $type);

        return match ($strType) {
            '1', '普通' => '1',
            '2', '当座' => '2',
            default => '1',
        };
    }

    /**
     * プレビュー用データを生成（指定行数まで）
     *
     * @param  array  $rows  振込データ配列
     * @param  int|null  $limit  表示行数（nullの場合は設定値を使用）
     * @return array プレビューデータ配列
     */
    public function preview(array $rows, ?int $limit = null): array
    {
        $limit = $limit ?? config('zengin.preview_limit', 20);
        $previewRows = array_slice($rows, 0, $limit);
        $preview = [];

        foreach ($previewRows as $index => $row) {
            $lineNumber = $index + 1;
            $hasError = false;
            $errorMessage = '';

            try {
                $this->buildLine($row, $lineNumber);
            } catch (\Exception $e) {
                $hasError = true;
                $errorMessage = $e->getMessage();
            }

            $preview[] = array_merge($row, [
                '_line_number' => $lineNumber,
                '_has_error' => $hasError,
                '_error_message' => $errorMessage,
            ]);
        }

        return $preview;
    }

    /**
     * 統計情報を取得
     *
     * @return array ['total_count' => int, 'total_amount' => int, 'errors' => array]
     */
    public function getStats(): array
    {
        return $this->stats;
    }
}
