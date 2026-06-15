<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashboxTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'cashbox_id',
        'type',
        'amount',
        'payment_method',
        'description',
        'related_model_type',
        'related_model_id',
        'user_id',
    ];

    public function cashbox()
    {
        return $this->belongsTo(Cashbox::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function relatedModel()
    {
        return $this->morphTo();
    }
}
