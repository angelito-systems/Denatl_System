<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Patient extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\PatientFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'dni',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'address',
        'occupation',
        'emergency_name',
        'emergency_phone',
        'blood_type',
        'allergies',
        'medical_notes',
        'family_history',
        'current_medication'
    ];

    protected $appends = ['age'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => isset($attributes['date_of_birth']) && $attributes['date_of_birth']
                ? Carbon::parse($attributes['date_of_birth'])->age 
                : null,
        );
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function treatments()
    {
        return $this->hasMany(Treatment::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
