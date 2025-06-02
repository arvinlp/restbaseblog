<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\EmailVerification;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_users()
    {
        EmailVerification::create([
            'email' => 'test@example.com',
            'code' => '123456',
            'status' => 1,
            'created_at' => now(),
        ]);
        $register = $this->postJson('/api/v1/auth/register', [
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $token = $register->json('token');
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/users');
        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_authenticated_user_can_view_user()
    {
        EmailVerification::create([
            'email' => 'test@example.com',
            'code' => '123456',
            'status' => 1,
            'created_at' => now(),
        ]);
        $register = $this->postJson('/api/v1/auth/register', [
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $token = $register->json('token');
        $userId = $register->json('user.id');
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/users/' . $userId);
        $response->assertStatus(200)
            ->assertJsonFragment(['email' => 'test@example.com']);
    }
}
