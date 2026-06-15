<?php

namespace App\Models;

use Database\Factories\TreatmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'category', 'base_price', 'estimated_duration_minutes'])]
class Treatment extends Model
{
    /** @use HasFactory<TreatmentFactory> */
    use HasFactory, SoftDeletes;
}
