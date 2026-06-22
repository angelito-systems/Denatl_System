<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'name',
        'status',
        'start_date',
        'end_date',
        'message',
        'target_audience',
        'send_time',
        'frequency',
        'channel',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}
