<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'remote_jid',
        'message_id',
        'sender_type',
        'content',
        'media_url',
        'type',
        'read_status',
    ];

    protected function casts(): array
    {
        return [
            'read_status' => 'boolean',
        ];
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
