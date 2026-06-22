<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientTreatment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id',
        'name',
        'start_date',
        'estimated_end_date',
        'status',
        'objectives',
        'description',
        'dentist_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'estimated_end_date' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function dentist()
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
