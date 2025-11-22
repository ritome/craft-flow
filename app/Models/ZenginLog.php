<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * 全銀フォーマット変換履歴モデル
 *
 * @property int $id
 * @property string $filename
 * @property string $file_path
 * @property int $total_count
 * @property int $total_amount
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class ZenginLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'filename',
        'file_path',
        'total_count',
        'total_amount',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_count' => 'integer',
            'total_amount' => 'integer',
        ];
    }

    /**
     * 関連するファイルが存在するかチェック
     */
    public function fileExists(): bool
    {
        return Storage::disk('local')->exists($this->file_path);
    }

    /**
     * 関連するファイルの内容を取得
     */
    public function getFileContent(): string
    {
        return Storage::disk('local')->get($this->file_path);
    }
}



