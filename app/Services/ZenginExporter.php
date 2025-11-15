<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\SjisPad;
use Illuminate\Support\Facades\Storage;

/**
 * 全銀フォーマットエクスポータ
 *
 * Excel入力データを全銀フォーマット（120バイト固定、Shift-JIS、CRLF）に変換
 */
class ZenginExporter
{
    /**
     * 変換統計情報
     */
    private array $stats = [
        'total_count' => 0,
        'total_amount' => 0,
        'replaced_count' => 0,
        'errors' => [],
    ];

    /**
     * データをエクスポート
     *
     * @param  array  $rows  入力データ配列
     * @return string Shift-JIS、CRLF、120バイト固定のテキスト
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
                $this->stats['total_amount'] += (int) ($row['amount'] ?? 0);
            } catch (\Exception $e) {
                $this->stats['errors'][] = [
                    'line' => $lineNumber,
                    'message' => $e->getMessage(),
                ];

                throw $e;
            }

            $lineNumber++;
        }

        // 改行で連結（末尾にも改行を付与）
        $newline = config('zengin.newline');

        return implode($newline, $lines).$newline;
    }

    /**
     * 1行分のレコードを構築
     *
     * @param  array  $row  行データ
     * @param  int  $lineNumber  行番号
     * @return string Shift-JISバイト列（120バイト）
     */
    private function buildLine(array $row, int $lineNumber): string
    {
        // フィールドバリデーション
        $this->validateRow($row, $lineNumber);

        $fields = config('zengin.fields');

        // データ区分（固定値 "2"）
        $dataType = SjisPad::padBytes('2', $fields['data_type']);

        // 金融機関コード（4桁、左ゼロ埋め）
        $bankCode = SjisPad::padNumber($row['bank_code'] ?? '', $fields['bank_code']);

        // 金融機関名（15バイト、半角カナ化）
        $bankName = SjisPad::padBytes($row['bank_name'] ?? '', $fields['bank_name']);

        // 支店コード（3桁、左ゼロ埋め）
        $branchCode = SjisPad::padNumber($row['branch_code'] ?? '', $fields['branch_code']);

        // 支店名（15バイト、半角カナ化）
        $branchName = SjisPad::padBytes($row['branch_name'] ?? '', $fields['branch_name']);

        // ダミー1（4バイト、スペース）
        $dummy1 = SjisPad::padBytes('', $fields['dummy1']);

        // 預金種目（1桁）
        $accountType = $this->convertAccountType($row['account_type'] ?? '');

        // 口座番号（7桁、左ゼロ埋め）
        $accountNumber = SjisPad::padNumber($row['account_number'] ?? '', $fields['account_number']);

        // 受取人名（30バイト、半角カナ化）
        $recipientName = SjisPad::padBytes($row['account_holder'] ?? '', $fields['recipient_name']);

        // 金額（10桁、左ゼロ埋め）
        $amount = SjisPad::padNumber($row['amount'] ?? 0, $fields['amount']);

        // ダミー2（10バイト、スペース）
        $dummy2 = SjisPad::padBytes('', $fields['dummy2']);

        // 顧客コード1（10バイト、スペース）
        $customerCode1 = SjisPad::padBytes('', $fields['customer_code1']);

        // 顧客コード2（10バイト、スペース）
        $customerCode2 = SjisPad::padBytes('', $fields['customer_code2']);

        // 全フィールドを連結
        $line = $dataType.$bankCode.$bankName.$branchCode.$branchName.$dummy1.
                $accountType.$accountNumber.$recipientName.$amount.
                $dummy2.$customerCode1.$customerCode2;

        return $line;
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
        // 金融機関コード：4桁数値
        if (! isset($row['bank_code']) || ! preg_match('/^\d{4}$/', (string) $row['bank_code'])) {
            throw new \RuntimeException(
                sprintf('行番号 %d: 金融機関コードは4桁の数値が必要です', $lineNumber)
            );
        }

        // 支店コード：3桁数値
        if (! isset($row['branch_code']) || ! preg_match('/^\d{1,3}$/', (string) $row['branch_code'])) {
            throw new \RuntimeException(
                sprintf('行番号 %d: 支店コードは3桁以内の数値が必要です', $lineNumber)
            );
        }

        // 預金種目：1=普通、2=当座
        $accountType = $row['account_type'] ?? '';
        if (! in_array($accountType, ['普通', '当座', '1', '2'], true)) {
            throw new \RuntimeException(
                sprintf('行番号 %d: 預金種目は「普通」または「当座」が必要です（値: %s）', $lineNumber, $accountType)
            );
        }

        // 口座番号：7桁以内の数値
        $accountNumber = (string) ($row['account_number'] ?? '');
        if (! preg_match('/^\d{1,7}$/', $accountNumber)) {
            throw new \RuntimeException(
                sprintf('行番号 %d: 口座番号は7桁以内の数値が必要です', $lineNumber)
            );
        }

        // 受取人名：必須
        if (empty($row['account_holder'])) {
            throw new \RuntimeException(
                sprintf('行番号 %d: 受取人名（口座名義）は必須です', $lineNumber)
            );
        }

        // 金額：整数
        $amount = $row['amount'] ?? 0;
        if (! is_numeric($amount) || $amount < 0) {
            throw new \RuntimeException(
                sprintf('行番号 %d: 金額は0以上の整数が必要です', $lineNumber)
            );
        }

        // 金額：10桁以内
        if (strlen((string) (int) $amount) > 10) {
            throw new \RuntimeException(
                sprintf('行番号 %d: 金額は10桁以内が必要です', $lineNumber)
            );
        }
    }

    /**
     * 預金種目を数値コードに変換
     *
     * @param  string  $accountType  預金種目
     * @return string 1文字のコード（"1"または"2"）
     */
    private function convertAccountType(string $accountType): string
    {
        return match (trim($accountType)) {
            '普通', '1' => '1',
            '当座', '2' => '2',
            default => '1', // デフォルトは普通
        };
    }

    /**
     * ファイルに保存
     *
     * @param  string  $content  保存する内容（Shift-JISバイト列）
     * @return string 保存したファイルの相対パス
     */
    public function store(string $content): string
    {
        $template = config('zengin.filename_template');
        $filename = str_replace(
            ['{Ymd_His}'],
            [date('Ymd_His')],
            $template
        );

        $path = config('zengin.storage_path').'/'.$filename;

        Storage::disk('local')->put($path, $content);

        return $path;
    }

    /**
     * プレビュー用にデータを整形
     *
     * @param  array  $rows  入力データ配列
     * @return array プレビューデータ
     */
    public function preview(array $rows): array
    {
        $limit = config('zengin.preview_limit', 5);
        $previewRows = array_slice($rows, 0, $limit);

        $preview = [];
        $lineNumber = 1;

        foreach ($previewRows as $row) {
            try {
                $line = $this->buildLine($row, $lineNumber);

                // Shift-JIS → UTF-8に戻して表示用に
                $display = mb_convert_encoding($line, 'UTF-8', 'SJIS-win');

                $preview[] = [
                    'line_number' => $lineNumber,
                    'content' => $display,
                    'byte_length' => strlen($line),
                    'is_valid' => strlen($line) === 120,
                    'bank_name' => $row['bank_name'] ?? '',
                    'amount' => $row['amount'] ?? 0,
                ];
            } catch (\Exception $e) {
                $preview[] = [
                    'line_number' => $lineNumber,
                    'error' => $e->getMessage(),
                    'is_valid' => false,
                ];
            }

            $lineNumber++;
        }

        return $preview;
    }

    /**
     * 統計情報をリセット
     */
    private function resetStats(): void
    {
        $this->stats = [
            'total_count' => 0,
            'total_amount' => 0,
            'replaced_count' => 0,
            'errors' => [],
        ];
    }

    /**
     * 統計情報を取得
     *
     * @return array 統計情報
     */
    public function getStats(): array
    {
        return $this->stats;
    }
}
