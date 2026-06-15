<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Raffle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'status', 'draw_date',
        'winner_count', 'winning_logic', 'winning_nth_position',
    ];

    protected $casts = [
        'draw_date' => 'date',
    ];

    public function prizes()
    {
        return $this->hasMany(RafflePrize::class);
    }

    public function participants()
    {
        return $this->hasMany(RaffleParticipant::class);
    }

    public function winners()
    {
        return $this->hasMany(RaffleParticipant::class)->where('is_winner', true);
    }
}
