<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * 委託精算書履歴モデル
 * 
 * Issue #16: 精算書発行履歴保存機能
 * 
 * @property int $id
 * @property string $billing_start_date 請求開始日
 * @property string $billing_end_date 請求終了日
 * @property int $client_count 委託先数
 * @property string|null $excel_path Excel ファイルパス
 * @property string|null $pdf_path PDF ファイルパス
 * @property float $total_sales_amount 総売上金額
 * @property float $total_commission 総手数料
 * @property float $total_payment_amount 総支払金額
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Settlement extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'settlement_number',
        'billing_start_date',
        'billing_end_date',
        'payment_date',
        'client_count',
        'excel_path',
        'pdf_path',
        'total_sales_amount',
        'total_commission',
        'total_payment_amount',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'billing_start_date' => 'date',
            'billing_end_date' => 'date',
            'payment_date' => 'date',
            'client_count' => 'integer',
            'total_sales_amount' => 'decimal:2',
            'total_commission' => 'decimal:2',
            'total_payment_amount' => 'decimal:2',
        ];
    }

    /**
     * 精算明細とのリレーション
     * 
     * @return HasMany
     */
    public function details(): HasMany
    {
        return $this->hasMany(SettlementDetail::class);
    }

    /**
     * Excel ファイルが存在するかチェック
     * 
     * Issue #17: 過去精算書履歴ダウンロード機能
     * 
     * Storageのexists()とfile_exists()の両方でチェック
     * （PHPSpreadsheetで直接保存したファイルにも対応）
     * 
     * @return bool
     */
    public function hasExcelFile(): bool
    {
        if (! $this->excel_path) {
            return false;
        }
        
        // Storageでチェック
        if (Storage::disk('local')->exists($this->excel_path)) {
            return true;
        }
        
        // file_exists()でもチェック（PHPSpreadsheetで直接保存したファイル用）
        $fullPath = storage_path('app/' . $this->excel_path);
        return file_exists($fullPath);
    }

    /**
     * PDF ファイルが存在するかチェック
     * 
     * Issue #17: 過去精算書履歴ダウンロード機能
     * 
     * @return bool
     */
    public function hasPdfFile(): bool
    {
        return $this->pdf_path && Storage::disk('local')->exists($this->pdf_path);
    }

    /**
     * Excel ファイルの内容を取得
     * 
     * Storageとfile_get_contents()の両方に対応
     * 
     * @return string
     */
    public function getExcelContent(): string
    {
        // まずStorageで取得を試みる
        if (Storage::disk('local')->exists($this->excel_path)) {
            return Storage::disk('local')->get($this->excel_path);
        }
        
        // file_get_contents()で取得（PHPSpreadsheetで直接保存したファイル用）
        $fullPath = storage_path('app/' . $this->excel_path);
        if (file_exists($fullPath)) {
            return file_get_contents($fullPath);
        }
        
        throw new \RuntimeException('Excelファイルが見つかりません: ' . $this->excel_path);
    }

    /**
     * PDF ファイルの内容を取得
     * 
     * @return string
     */
    public function getPdfContent(): string
    {
        return Storage::disk('local')->get($this->pdf_path);
    }

    /**
     * 請求期間の表示用文字列を取得
     * 
     * @return string
     */
    public function getBillingPeriodAttribute(): string
    {
        return $this->billing_start_date->format('Y年m月d日')
            .' 〜 '
            .$this->billing_end_date->format('Y年m月d日');
    }
}



