<?php

namespace Database\Factories;

use App\Models\KnowledgeBase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeBase>
 */
class KnowledgeBaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'organization_id' => fn (array $attributes) => User::query()->find($attributes['user_id'])?->organization_id,
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'welcome_message' => 'Hi! Ask me anything about our docs.',
            'allowed_origins' => ['*'],
            'primary_color' => '#4f46e5',
            'is_active' => true,
        ];
    }
}
