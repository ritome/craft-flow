<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    protected $fillable = [
        'experience_program_id',
        'reservation_date',
        'reservation_time',
        'customer_name',
        'customer_phone',
        'customer_email',
        'participant_count',
        'reservation_source',
        'status',
        'notes',
    ];

    /**
     * reservation_time のアクセサを定義し、データベースの 'H:i:s' 形式から 'H:i' 形式に整形します。
     * これにより、Livewireコンポーネントやビューで常に秒なしの形式が取得できます。
     */
    protected function reservationTime(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => substr($value, 0, 5), // '10:00:00' -> '10:00' に強制的にトリミング
        );
    }

    protected $casts = [
        'reservation_date' => 'datetime', // ★ この行を追加
    ];

    public function experienceProgram()
    {
        // Reservationの'experience_program_id'とExperienceProgramの'experience_program_id'を紐付けます
        return $this->belongsTo(ExperienceProgram::class, 'experience_program_id', 'experience_program_id');
    }
}
