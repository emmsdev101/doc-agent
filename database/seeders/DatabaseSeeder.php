<?php

namespace Database\Seeders;

use App\Models\KnowledgeBase;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->create([
            'name' => 'Acme Docs',
            'slug' => 'acme-docs',
        ]);

        User::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Acme Admin',
            'email' => 'admin@docagent.test',
            'password' => 'password',
        ]);

        KnowledgeBase::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Product Help Center',
            'description' => 'Public answers for the marketing site.',
            'welcome_message' => 'Hi! I can help with product questions.',
            'allowed_origins' => ['*'],
            'primary_color' => '#4f46e5',
        ]);
    }
}
