<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'patient_id',
        'phone_number',
        'bot_status',
        'bot_state',
        'bot_data',
        'assigned_to',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'bot_data' => 'array',
            'last_message_at' => 'datetime',
        ];
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function messages()
    {
        return $this->hasMany(WhatsappMessage::class);
    }

    public function advisor()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
