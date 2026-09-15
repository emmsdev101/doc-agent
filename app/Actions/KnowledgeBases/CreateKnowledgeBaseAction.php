<?php

namespace App\Actions\KnowledgeBases;

use App\Models\KnowledgeBase;
use App\Models\User;

final class CreateKnowledgeBaseAction
{
    /**
     * @param  array{name: string, description?: string|null, welcome_message?: string|null, system_prompt?: string|null, allowed_origins?: string|null, primary_color?: string|null}  $payload
     */
    public function execute(User $user, array $payload): KnowledgeBase
    {
        return $user->organization->knowledgeBases()->create([
            'user_id' => $user->id,
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'welcome_message' => $payload['welcome_message'] ?? 'Hi! Ask me anything about our docs.',
            'system_prompt' => $payload['system_prompt'] ?? null,
            'allowed_origins' => $this->parseOrigins($payload['allowed_origins'] ?? '*'),
            'primary_color' => $payload['primary_color'] ?? '#4f46e5',
            'is_active' => true,
        ]);
    }

    /**
     * @return list<string>
     */
    public function parseOrigins(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return ['*'];
        }

        $origins = collect(preg_split('/[\s,]+/', $raw) ?: [])
            ->map(fn (string $origin): string => rtrim(trim($origin), '/'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $origins === [] ? ['*'] : $origins;
    }
}
