<?php

namespace Tests\Feature;

use App\Models\KnowledgeBase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WidgetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_config_requires_a_valid_token(): void
    {
        $this->getJson('/api/v1/widget/'.fake()->uuid())->assertNotFound();
    }

    public function test_widget_config_is_public_for_an_active_knowledge_base(): void
    {
        $user = User::factory()->create();
        $knowledgeBase = KnowledgeBase::factory()->create([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'name' => 'Support KB',
        ]);

        $this->getJson('/api/v1/widget/'.$knowledgeBase->widget_token)
            ->assertOk()
            ->assertJsonPath('name', 'Support KB');
    }

    public function test_chat_rejects_disallowed_origins(): void
    {
        $user = User::factory()->create();
        $knowledgeBase = KnowledgeBase::factory()->create([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'allowed_origins' => ['https://allowed.example'],
        ]);

        $this->withHeaders(['Origin' => 'https://evil.example'])
            ->postJson('/api/v1/chat', [
                'kb_id' => $knowledgeBase->widget_token,
                'message' => 'Hello',
                'stream' => false,
            ])
            ->assertForbidden();
    }
}
