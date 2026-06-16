<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name'])]
class TreatmentCategory extends Model
{
    public function treatments()
    {
        return $this->hasMany(Treatment::class);
    }
}
