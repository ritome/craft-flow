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
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * ファイルの完全なパスを取得
     *
     * @return string
     */
    public function getFullPathAttribute(): string
    {
        return Storage::disk('local')->path($this->file_path);
    }

    /**
     * ファイルが存在するか確認
     *
     * @return bool
     */
    public function fileExists(): bool
    {
        return Storage::disk('local')->exists($this->file_path);
    }

    /**
     * ファイルの内容を取得
     *
     * @return string|null
     */
    public function getFileContent(): ?string
    {
        if (! $this->fileExists()) {
            return null;
        }

        return Storage::disk('local')->get($this->file_path);
    }

    /**
     * 変換日時をフォーマットして取得
     *
     * @return string
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('Y-m-d H:i');
    }

    /**
     * 合計金額をフォーマットして取得
     *
     * @return string
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->total_amount);
    }
}
