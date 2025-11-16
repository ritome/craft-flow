<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

/**
 * PDFインポート処理を統括するサービス
 */
class PdfImportService
{
    public function __construct(
        private readonly PdfReader $pdfReader,
        private readonly ParserFactory $parserFactory,
        private readonly Normalizer $normalizer,
        private readonly Aggregator $aggregator,
        private readonly ExcelExporter $excelExporter,
    ) {}

    /**
     * PDFファイルをインポートしてExcelを出力
     *
     * @param  array  $pdfFiles  アップロードされたPDFファイルパス配列
     * @return string 出力されたExcelファイルパス
     *
     * @throws InvalidArgumentException PDFファイルが存在しない、または読み取れない場合
     * @throws RuntimeException 処理中にエラーが発生した場合
     */
    public function import(array $pdfFiles): string
    {
        if (empty($pdfFiles)) {
            throw new InvalidArgumentException('PDFファイルが指定されていません');
        }

        Log::info('PDFインポート処理を開始', [
            'files_count' => count($pdfFiles),
        ]);

        try {
            $normalizedDataList = [];
            $successCount = 0;
            $failedFiles = [];

            // 各PDFファイルを処理
            foreach ($pdfFiles as $index => $pdfFile) {
                try {
                    Log::info("PDFファイル処理開始: {$index}", [
                        'file' => basename($pdfFile),
                    ]);

                    // 1. PDFからテキスト抽出
                    $text = $this->pdfReader->extract($pdfFile);

                    // デバッグ: 抽出されたテキストの最初の500文字をログ出力
                    Log::debug("PDFから抽出されたテキスト: {$index}", [
                        'file' => basename($pdfFile),
                        'text_length' => strlen($text),
                        'text_preview' => mb_substr($text, 0, 500),
                        'has_register_pattern' => preg_match('/レジ番号:POS/u', $text) === 1,
                        'has_business_date_pattern' => preg_match('/営業日:令和/u', $text) === 1,
                    ]);

                    // 2. 適切なパーサーを取得してパース
                    $parser = $this->parserFactory->getParser($text);
                    $parsedData = $parser->parse($text);

                    // 3. データを正規化
                    $normalizedData = $this->normalizer->normalize($parsedData);
                    $normalizedDataList[] = $normalizedData;

                    $successCount++;

                    Log::info("PDFファイル処理成功: {$index}", [
                        'file' => basename($pdfFile),
                        'total' => $normalizedData['total'] ?? 0,
                        'items_count' => count($normalizedData['items'] ?? []),
                    ]);
                } catch (InvalidArgumentException $e) {
                    $failedFiles[] = [
                        'file' => basename($pdfFile),
                        'error' => $e->getMessage(),
                    ];

                    Log::warning("PDFファイル処理スキップ: {$index}", [
                        'file' => basename($pdfFile),
                        'error' => $e->getMessage(),
                    ]);
                } catch (\Exception $e) {
                    $failedFiles[] = [
                        'file' => basename($pdfFile),
                        'error' => $e->getMessage(),
                    ];

                    Log::error("PDFファイル処理エラー: {$index}", [
                        'file' => basename($pdfFile),
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            // 処理に成功したファイルが1つもない場合はエラー
            if (empty($normalizedDataList)) {
                throw new RuntimeException(
                    '処理可能なPDFファイルがありませんでした。' .
                        'すべてのファイルでエラーが発生しました。'
                );
            }

            Log::info('PDFパース処理完了', [
                'success_count' => $successCount,
                'failed_count' => count($failedFiles),
            ]);

            // 4. データを集計
            $aggregatedData = $this->aggregator->aggregate($normalizedDataList);

            Log::info('集計処理完了', [
                'total_sales' => $aggregatedData['total_sales'] ?? 0,
                'items_count' => count($aggregatedData['items'] ?? []),
            ]);

            // 5. Excelとして出力
            $excelPath = $this->excelExporter->export($aggregatedData);

            Log::info('PDFインポート処理完了', [
                'excel_path' => $excelPath,
                'success_count' => $successCount,
                'failed_count' => count($failedFiles),
            ]);

            return $excelPath;
        } catch (\Exception $e) {
            Log::error('PDFインポート処理失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
