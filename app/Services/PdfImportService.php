<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ImportHistory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
     * @return array 出力されたExcelファイルパスと履歴ID ['excel_path' => string, 'history_id' => int]
     *
     * @throws InvalidArgumentException PDFファイルが存在しない、または読み取れない場合
     * @throws RuntimeException 処理中にエラーが発生した場合
     */
    public function import(array $pdfFiles): array
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
                    '処理可能なPDFファイルがありませんでした。'.
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

            // 6. 履歴を保存
            $history = $this->saveHistory(
                fileCount: count($pdfFiles),
                successCount: $successCount,
                failedCount: count($failedFiles),
                excelPath: $excelPath,
                fileDetails: $this->buildFileDetails($pdfFiles, $failedFiles),
                totalSales: $aggregatedData['summary']['total_sales'] ?? $aggregatedData['total_sales'] ?? 0
            );

            Log::info('PDFインポート処理完了', [
                'excel_path' => $excelPath,
                'history_id' => $history->id,
                'success_count' => $successCount,
                'failed_count' => count($failedFiles),
            ]);

            return [
                'excel_path' => $excelPath,
                'history_id' => $history->id,
            ];
        } catch (\Exception $e) {
            Log::error('PDFインポート処理失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * 集計履歴を保存
     *
     * @param  int  $fileCount  アップロードファイル数
     * @param  int  $successCount  処理成功ファイル数
     * @param  int  $failedCount  処理失敗ファイル数
     * @param  string  $excelPath  一時Excelファイルパス
     * @param  array  $fileDetails  ファイル処理詳細
     * @param  float  $totalSales  売上合計金額
     */
    private function saveHistory(
        int $fileCount,
        int $successCount,
        int $failedCount,
        string $excelPath,
        array $fileDetails,
        float $totalSales
    ): ImportHistory {
        // Excelファイルを永続ストレージに保存
        $permanentPath = $this->moveExcelToPermanentStorage($excelPath);

        return ImportHistory::create([
            'import_date' => now(),
            'file_count' => $fileCount,
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'excel_path' => $permanentPath,
            'file_details' => $fileDetails,
            'total_sales' => $totalSales,
        ]);
    }

    /**
     * Excelファイルを永続ストレージに移動
     *
     * @param  string  $tempExcelPath  一時Excelファイルパス
     * @return string 永続ストレージのパス
     */
    private function moveExcelToPermanentStorage(string $tempExcelPath): string
    {
        $fileName = basename($tempExcelPath);
        $permanentPath = 'exports/'.date('Y/m/d').'/'.$fileName;

        // ファイルをコピー
        Storage::disk('local')->put(
            $permanentPath,
            file_get_contents($tempExcelPath)
        );

        Log::debug('Excelファイルを永続ストレージに保存', [
            'temp_path' => $tempExcelPath,
            'permanent_path' => $permanentPath,
        ]);

        return $permanentPath;
    }

    /**
     * ファイル処理詳細を構築
     *
     * @param  array  $pdfFiles  アップロードされたPDFファイルパス配列
     * @param  array  $failedFiles  失敗ファイル情報配列
     * @return array ファイル処理詳細
     */
    private function buildFileDetails(array $pdfFiles, array $failedFiles): array
    {
        return [
            'uploaded_files' => array_map(fn ($file) => basename($file), $pdfFiles),
            'failed_files' => $failedFiles,
            'processed_at' => now()->toIso8601String(),
        ];
    }
}
