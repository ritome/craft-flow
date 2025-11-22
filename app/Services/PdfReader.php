<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;
use Spatie\PdfToText\Exceptions\CouldNotExtractText;
use Spatie\PdfToText\Exceptions\PdfNotFound;
use Spatie\PdfToText\Pdf;

/**
 * PDFからテキストを抽出するサービス
 */
class PdfReader
{
    /**
     * PDFファイルからテキストを抽出
     *
     * @param  string  $filePath  PDFファイルパス
     * @return string 抽出されたテキスト
     *
     * @throws InvalidArgumentException ファイルが存在しない場合
     * @throws RuntimeException PDFからテキストを抽出できない場合
     */
    public function extract(string $filePath): string
    {
        // ファイルの存在確認
        if (! file_exists($filePath)) {
            throw new InvalidArgumentException("PDFファイルが見つかりません: {$filePath}");
        }

        // ファイルが読み取り可能か確認
        if (! is_readable($filePath)) {
            throw new InvalidArgumentException("PDFファイルが読み取れません: {$filePath}");
        }

        try {
            // PDFからテキストを抽出（-layoutオプションでレイアウトを保持）
            $text = Pdf::getText($filePath, null, ['-layout']);

            // 空のテキストの場合は警告
            if (empty(trim($text))) {
                throw new RuntimeException("PDFからテキストを抽出できませんでした（空のコンテンツ）: {$filePath}");
            }

            return $text;
        } catch (PdfNotFound $e) {
            throw new InvalidArgumentException("PDFファイルが見つかりません: {$filePath}", 0, $e);
        } catch (CouldNotExtractText $e) {
            throw new RuntimeException("PDFからテキストを抽出できませんでした: {$e->getMessage()}", 0, $e);
        } catch (\Exception $e) {
            throw new RuntimeException("PDFの読み込み中にエラーが発生しました: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * PDFファイルからテキストを抽出（旧メソッド名との互換性維持）
     *
     * @param  string  $filePath  PDFファイルパス
     * @return string 抽出されたテキスト
     *
     * @throws InvalidArgumentException ファイルが存在しない場合
     * @throws RuntimeException PDFからテキストを抽出できない場合
     */
    public function extractText(string $filePath): string
    {
        return $this->extract($filePath);
    }
}
