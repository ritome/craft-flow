<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * インポート履歴モデル
 */
class ImportHistory extends Model
{
    use HasFactory;

    /**
     * 複数代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'import_date',
        'file_count',
        'success_count',
        'failed_count',
        'excel_path',
        'file_details',
        'total_sales',
    ];

    /**
     * 属性のキャスト
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'import_date' => 'datetime',
            'file_details' => 'array',
            'total_sales' => 'decimal:2',
        ];
    }

    /**
     * Excelファイルが存在するかチェック
     */
    public function excelFileExists(): bool
    {
        return Storage::disk('local')->exists($this->excel_path);
    }

    /**
     * Excelファイルのフルパスを取得
     */
    public function getExcelFullPath(): string
    {
        return Storage::disk('local')->path($this->excel_path);
    }
}
