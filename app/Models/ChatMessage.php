<?php

namespace App\Models;

use App\Enums\ChatRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasUuids;

    protected $fillable = [
        'chat_session_id',
        'role',
        'content',
        'citations',
    ];

    protected function casts(): array
    {
        return [
            'role' => ChatRole::class,
            'citations' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }
}
