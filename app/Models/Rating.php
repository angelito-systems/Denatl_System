<?php

namespace App\Models;

use Database\Factories\RatingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    /** @use HasFactory<RatingFactory> */
    use HasFactory;

    protected $fillable = ['patient_id', 'score', 'comment', 'source'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
