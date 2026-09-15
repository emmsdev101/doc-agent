<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_register_an_organization(): void
    {
        $response = $this->post('/register', [
            'name' => 'Jamie Rivera',
            'organization' => 'Northwind',
            'email' => 'jamie@northwind.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'jamie@northwind.test']);
        $this->assertDatabaseHas('organizations', ['name' => 'Northwind']);
    }

    public function test_users_can_authenticate(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@docagent.test',
            'password' => 'password',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }
}
