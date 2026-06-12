<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evolution extends Model
{
    protected $fillable = [
        'patient_id',
        'appointment_id',
        'dentist_id',
        'description',
        'odontogram_data',
    ];

    protected $casts = [
        'odontogram_data' => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function dentist()
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }
}
