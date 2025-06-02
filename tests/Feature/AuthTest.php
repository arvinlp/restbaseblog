<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        // create verified email for register
        \App\Models\EmailVerification::create([
            'email' => 'test@example.com',
            'code' => '123456',
            'status' => 1,
            'created_at' => now(),
        ]);
        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'token', 'user']);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'token', 'user']);
    }
}
