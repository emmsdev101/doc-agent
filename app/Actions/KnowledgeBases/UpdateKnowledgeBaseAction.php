<?php

namespace App\Actions\KnowledgeBases;

use App\Models\KnowledgeBase;
use Illuminate\Support\Str;

final class UpdateKnowledgeBaseAction
{
    public function __construct(
        private readonly CreateKnowledgeBaseAction $create,
    ) {
    }

    /**
     * @param  array{name?: string, description?: string|null, welcome_message?: string|null, system_prompt?: string|null, allowed_origins?: string|null, primary_color?: string|null, is_active?: bool, rotate_token?: bool}  $payload
     */
    public function execute(KnowledgeBase $knowledgeBase, array $payload): KnowledgeBase
    {
        $knowledgeBase->fill([
            'name' => $payload['name'] ?? $knowledgeBase->name,
            'description' => array_key_exists('description', $payload) ? $payload['description'] : $knowledgeBase->description,
            'welcome_message' => $payload['welcome_message'] ?? $knowledgeBase->welcome_message,
            'system_prompt' => array_key_exists('system_prompt', $payload) ? $payload['system_prompt'] : $knowledgeBase->system_prompt,
            'primary_color' => $payload['primary_color'] ?? $knowledgeBase->primary_color,
            'is_active' => $payload['is_active'] ?? $knowledgeBase->is_active,
        ]);

        if (array_key_exists('allowed_origins', $payload)) {
            $knowledgeBase->allowed_origins = $this->create->parseOrigins($payload['allowed_origins']);
        }

        if (! empty($payload['rotate_token'])) {
            $knowledgeBase->widget_token = (string) Str::uuid();
        }

        $knowledgeBase->save();

        return $knowledgeBase;
    }
}
