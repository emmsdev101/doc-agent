<?php

namespace App\Actions\Auth;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RegisterOrganizationAction
{
    /**
     * @param  array{name: string, email: string, password: string, organization: string}  $payload
     */
    public function execute(array $payload): User
    {
        return DB::transaction(function () use ($payload): User {
            $organization = Organization::query()->create([
                'name' => $payload['organization'],
                'slug' => $this->uniqueSlug($payload['organization']),
            ]);

            $user = User::query()->create([
                'organization_id' => $organization->id,
                'name' => $payload['name'],
                'email' => $payload['email'],
                'password' => $payload['password'],
            ]);

            event(new Registered($user));

            return $user;
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'organization';
        $slug = $base;
        $i = 1;

        while (Organization::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
