<?php

declare(strict_types=1);

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * シート3: レジ別詳細
 *
 * 各レジの詳細データを表示
 */
class RegisterDetailSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths, WithEvents
{
    private array $subtotalRows = [];

    public function __construct(
        private readonly array $aggregatedData
    ) {}

    public function title(): string
    {
        return 'レジ別詳細';
    }

    public function headings(): array
    {
        return [
            'レジ番号',
            '商品コード',
            '商品名',
            '単価',
            '数量',
            '小計',
        ];
    }

    public function array(): array
    {
        $data = [];
        $currentRow = 2; // ヘッダー行の次から

        // レジ別のデータ行(レジ番号の昇順にソート)
        $registers = $this->aggregatedData['registers'] ?? [];
        usort($registers, function ($a, $b) {
            return $a['register_id'] <=> $b['register_id'];
        });

        // レジ別にデータを出力
        foreach ($registers as $register) {
            // 各レジの商品明細
            foreach ($register['items'] as $item) {
                $data[] = [
                    $register['register_id'],
                    $item['product_code'],
                    $item['product_name'],
                    '¥' . number_format($item['unit_price']),
                    $item['quantity'],
                    '¥' . number_format($item['subtotal']),
                ];
                $currentRow++;
            }

            // レジ小計行
            $data[] = [
                $register['register_id'] . ' 小計',
                '',
                '',
                '',
                $register['quantity_total'],
                '¥' . number_format($register['sales_total']),
            ];
            $this->subtotalRows[] = $currentRow;
            $currentRow++;
        }

        // 総合計行
        $summary = $this->aggregatedData['summary'] ?? [];
        $data[] = [
            '総合計',
            '',
            '',
            '',
            $summary['total_quantity'] ?? 0,
            '¥' . number_format($summary['total_sales'] ?? 0),
        ];
        $this->subtotalRows[] = $currentRow;

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        $styles = [
            // ヘッダー行のスタイル
            1 => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'D9E2F3'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];

        // 小計行と総合計行のスタイル
        $lastRow = end($this->subtotalRows);
        foreach ($this->subtotalRows as $row) {
            $isTotal = ($row === $lastRow);
            $styles[$row] = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => $isTotal ? 'FFF2CC' : 'FFF9E6'],
                ],
            ];
        }

        return $styles;
    }

    // 出力シートのカラム幅定義
    public function columnWidths(): array
    {
        return [
            'A' => 15,  // レジ番号
            'B' => 20,  // 商品コード
            'C' => 60,  // 商品名
            'D' => 12,  // 単価
            'E' => 12,  // 数量
            'F' => 15,  // 小計
        ];
    }

    // ヘッダー行にオートフィルタを設定
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // ヘッダー行にオートフィルタを設定
                $lastColumn = 'F'; // 最後の列（小計）
                $lastRow = count($this->aggregatedData['registers'] ?? []) + 1; // データ行数 + ヘッダー
                $event->sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");
            },
        ];
    }
}
