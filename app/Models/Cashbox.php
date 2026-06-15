<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cashbox extends Model
{
    use HasFactory;

    protected $fillable = [
        'opening_amount',
        'closing_amount',
        'status',
        'opened_at',
        'closed_at',
        'opened_by_id',
        'closed_by_id',
        'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by_id');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by_id');
    }

    public function transactions()
    {
        return $this->hasMany(CashboxTransaction::class);
    }
}
