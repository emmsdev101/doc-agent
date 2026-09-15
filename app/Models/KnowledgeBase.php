<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class KnowledgeBase extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'user_id',
        'name',
        'description',
        'widget_token',
        'welcome_message',
        'system_prompt',
        'allowed_origins',
        'primary_color',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'allowed_origins' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $knowledgeBase): void {
            if (blank($knowledgeBase->widget_token)) {
                $knowledgeBase->widget_token = (string) Str::uuid();
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class);
    }

    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }

    public function allowsOrigin(?string $origin): bool
    {
        $origins = $this->allowed_origins ?? [];

        if ($origins === [] || in_array('*', $origins, true)) {
            return true;
        }

        if ($origin === null || $origin === '') {
            return false;
        }

        return collect($origins)
            ->contains(fn (string $allowed): bool => strcasecmp(rtrim($allowed, '/'), rtrim($origin, '/')) === 0);
    }

    public function embedSnippet(string $appUrl): string
    {
        $src = rtrim($appUrl, '/').'/widget.js';

        return '<script src="'.e($src).'" data-kb-id="'.e($this->widget_token).'" async></script>';
    }
}
