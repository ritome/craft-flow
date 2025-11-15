<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\SjisPad;
use App\Support\TextNormalizer;

/**
 * 全銀フォーマット出力サービス
 *
 * Excel/配列データを全銀フォーマット（120バイト固定長・Shift-JIS・CRLF）に変換
 */
class ZenginExporter
{
    /**
     * 統計情報
     *
     * @var array{total_count: int, total_amount: int, errors: array}
     */
    private array $stats = [
        'total_count' => 0,
        'total_amount' => 0,
        'errors' => [],
    ];

    /**
     * データ配列を全銀フォーマットに変換
     *
     * @param  array  $rows  変換対象データ（行の配列）
     * @return string Shift-JISバイナリ文字列（CRLF改行）
     *
     * @throws \RuntimeException バリデーションエラー時
     */
    public function export(array $rows): string
    {
        $this->resetStats();
        $lines = [];
        $lineNumber = 1;

        foreach ($rows as $row) {
            try {
                $line = $this->buildLine($row, $lineNumber);

                // 120バイト厳密検証
                if (strlen($line) !== config('zengin.line_length')) {
                    throw new \RuntimeException(
                        sprintf(
                            '行番号 %d: 行長が %d バイトです（期待値: 120バイト）',
                            $lineNumber,
                            strlen($line)
                        )
                    );
                }

                $lines[] = $line;
                $this->stats['total_count']++;
                $this->stats['total_amount'] += (int) ($row['amount'] ?? $row['振込金額'] ?? 0);
            } catch (\Exception $e) {
                $this->stats['errors'][] = [
                    'line' => $lineNumber,
                    'message' => $e->getMessage(),
                ];

                throw $e;
            }

            $lineNumber++;
        }

        // CRLF改行で連結（末尾にも改行を付与）
        $newline = config('zengin.newline');

        return implode($newline, $lines).$newline;
    }

    /**
     * 1行のデータを120バイト固定長レコードに構築
     *
     * @param  array  $row  行データ
     * @param  int  $lineNumber  行番号（エラー表示用）
     * @return string Shift-JISバイナリ文字列（120バイト）
     *
     * @throws \RuntimeException バリデーションエラー時
     */
    private function buildLine(array $row, int $lineNumber): string
    {
        // 日本語キーと英語キーの両方に対応
        $bankCode = $row['bank_code'] ?? $row['金融機関コード'] ?? '';
        $bankName = $row['bank_name'] ?? $row['金融機関名'] ?? '';
        $branchCode = $row['branch_code'] ?? $row['支店コード'] ?? '';
        $branchName = $row['branch_name'] ?? $row['支店名'] ?? '';
        $accountType = $row['account_type'] ?? $row['預金種目'] ?? '';
        $accountNumber = $row['account_number'] ?? $row['口座番号'] ?? '';
        $recipientName = $row['account_holder'] ?? $row['口座名義（カナ）'] ?? '';
        $amount = $row['amount'] ?? $row['振込金額'] ?? '';

        // バリデーション
        $this->validateRow($row, $lineNumber);

        // 預金種目を数値に変換
        $accountTypeCode = $this->convertAccountType($accountType);

        // フィールド構築（全てShift-JISバイナリ）
        $fields = config('zengin.fields');

        $dataType = '2'; // データ区分は固定で"2"
        $bankCodePad = SjisPad::padNumber($bankCode, $fields['bank_code']);
        $bankNamePad = SjisPad::padBytes($bankName, $fields['bank_name']);
        $branchCodePad = SjisPad::padNumber($branchCode, $fields['branch_code']);
        $branchNamePad = SjisPad::padBytes($branchName, $fields['branch_name']);
        $dummy1 = str_repeat(' ', $fields['dummy1']);
        $accountTypePad = (string) $accountTypeCode;
        $accountNumberPad = SjisPad::padNumber($accountNumber, $fields['account_number']);
        $recipientNamePad = SjisPad::padBytes($recipientName, $fields['recipient_name']);
        $amountPad = SjisPad::padNumber($amount, $fields['amount']);
        $dummy2 = str_repeat(' ', $fields['dummy2']);
        $customerCode1 = str_repeat(' ', $fields['customer_code1']);
        $customerCode2 = str_repeat(' ', $fields['customer_code2']);

        // 連結
        return $dataType.
            $bankCodePad.
            $bankNamePad.
            $branchCodePad.
            $branchNamePad.
            $dummy1.
            $accountTypePad.
            $accountNumberPad.
            $recipientNamePad.
            $amountPad.
            $dummy2.
            $customerCode1.
            $customerCode2;
    }

    /**
     * 行データのバリデーション
     *
     * @param  array  $row  行データ
     * @param  int  $lineNumber  行番号
     *
     * @throws \RuntimeException バリデーションエラー時
     */
    private function validateRow(array $row, int $lineNumber): void
    {
        $bankCode = $row['bank_code'] ?? $row['金融機関コード'] ?? '';
        $branchCode = $row['branch_code'] ?? $row['支店コード'] ?? '';
        $accountType = $row['account_type'] ?? $row['預金種目'] ?? '';
        $accountNumber = $row['account_number'] ?? $row['口座番号'] ?? '';
        $recipientName = $row['account_holder'] ?? $row['口座名義（カナ）'] ?? '';
        $amount = $row['amount'] ?? $row['振込金額'] ?? '';

        // 金融機関コード（4桁数値）
        if (! preg_match('/^\d{4}$/', (string) $bankCode)) {
            throw new \RuntimeException(
                sprintf('行番号 %d: 金融機関コードは4桁の数値である必要があります（現在: %s）', $lineNumber, $bankCode)
            );
        }

        // 支店コード（1-3桁数値）
        if (! preg_match('/^\d{1,3}$/', (string) $branchCode)) {
            throw new \RuntimeException(
                sprintf('行番号 %d: 支店コードは1-3桁の数値である必要があります（現在: %s）', $lineNumber, $branchCode)
            );
        }

        // 預金種目（普通 or 当座）
        if (! in_array($accountType, ['普通', '当座', '1', '2'], true)) {
            throw new \RuntimeException(
                sprintf('行番号 %d: 預金種目は「普通」または「当座」である必要があります（現在: %s）', $lineNumber, $accountType)
            );
        }

        // 口座番号（1-7桁数値）
        if (! preg_match('/^\d{1,7}$/', (string) $accountNumber)) {
            throw new \RuntimeException(
                sprintf('行番号 %d: 口座番号は1-7桁の数値である必要があります（現在: %s）', $lineNumber, $accountNumber)
            );
        }

        // 受取人名（必須）
        if (empty(trim((string) $recipientName))) {
            throw new \RuntimeException(
                sprintf('行番号 %d: 受取人名が空白です', $lineNumber)
            );
        }

        // 金額（0以上の整数、10桁以内）
        if (! preg_match('/^\d{1,10}$/', (string) $amount)) {
            throw new \RuntimeException(
                sprintf('行番号 %d: 金額は0以上10桁以内の整数である必要があります（現在: %s）', $lineNumber, $amount)
            );
        }
    }

    /**
     * 預金種目を数値コードに変換
     *
     * @param  string  $type  預金種目（普通/当座/1/2）
     * @return int 預金種目コード（1=普通, 2=当座）
     */
    private function convertAccountType(string $type): int
    {
        return match ($type) {
            '普通', '1' => 1,
            '当座', '2' => 2,
            default => 1,
        };
    }

    /**
     * 統計情報を取得
     *
     * @return array{total_count: int, total_amount: int, errors: array}
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * 統計情報をリセット
     */
    private function resetStats(): void
    {
        $this->stats = [
            'total_count' => 0,
            'total_amount' => 0,
            'errors' => [],
        ];
    }

    /**
     * プレビュー用にN件のデータを取得
     *
     * @param  array  $rows  データ配列
     * @param  int|null  $limit  取得件数（nullの場合は設定値）
     * @return array プレビューデータ
     */
    public function preview(array $rows, ?int $limit = null): array
    {
        $limit = $limit ?? config('zengin.preview_limit', 20);
        $previewRows = array_slice($rows, 0, $limit);

        return array_map(function ($row, $index) {
            $row['_line_number'] = $index + 1;
            $row['_has_error'] = false;
            $row['_error_message'] = null;

            try {
                $this->validateRow($row, $index + 1);
            } catch (\Exception $e) {
                $row['_has_error'] = true;
                $row['_error_message'] = $e->getMessage();
            }

            return $row;
        }, $previewRows, array_keys($previewRows));
    }
}

