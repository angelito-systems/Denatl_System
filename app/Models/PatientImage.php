<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientImage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id',
        'uploaded_by',
        'category_id',
        'title',
        'description',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'taken_at',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
        'file_size' => 'integer',
    ];

    protected $appends = ['url'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function category()
    {
        return $this->belongsTo(ImageCategory::class, 'category_id');
    }

    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => route('patient-images.show', $this->id),
        );
    }
}
