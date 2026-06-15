<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RaffleParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'raffle_id', 'patient_id', 'phone_number',
        'is_winner', 'raffle_prize_id',
    ];

    protected $casts = [
        'is_winner' => 'boolean',
    ];

    public function raffle()
    {
        return $this->belongsTo(Raffle::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function prize()
    {
        return $this->belongsTo(RafflePrize::class, 'raffle_prize_id');
    }
}
