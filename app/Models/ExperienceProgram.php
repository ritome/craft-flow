<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExperienceProgram extends Model
{
    use HasFactory;

    protected $table = 'experience_programs';

    // 🚨 確実にこの行がファイルにあることを確認してください
    protected $primaryKey = 'experience_program_id';

    protected $fillable = [
        'name',
        'description',
        'duration',
        'capacity',
        'price',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'experience_program_id', 'experience_program_id');
    }
}
