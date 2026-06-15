<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Patient extends Model implements HasMedia
{
    /** @use HasFactory<PatientFactory> */
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
        'current_medication',
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

    public function evolutions()
    {
        return $this->hasMany(Evolution::class)->orderBy('created_at', 'desc');
    }

    public function latestEvolution()
    {
        return $this->hasOne(Evolution::class)->latestOfMany();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function treatmentContracts(): HasMany
    {
        return $this->hasMany(TreatmentContract::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function whatsappMessages()
    {
        return $this->hasManyThrough(WhatsappMessage::class, Conversation::class);
    }

    public function patientImages(): HasMany
    {
        return $this->hasMany(PatientImage::class)->orderBy('taken_at', 'desc')->orderBy('created_at', 'desc');
    }
}
