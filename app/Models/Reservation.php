<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = ['experience_program_id', 'reservation_date', 'reservation_time', 'customer_name', 'customer_phone', 'customer_email', 'participant_count', 'reservation_source', 'status', 'notes'];
}
