<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\BlogPost;
use App\Models\EmailVerification;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_post()
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
            ->postJson('/api/v1/posts/new', [
                'title' => 'Sample Post',
                'short' => 'Short desc',
                'content' => 'Full content',
            ]);
        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'data']);
    }

    public function test_anyone_can_view_post()
    {
        $post = BlogPost::factory()->create([
            'title' => 'Sample Post',
            'short' => 'Short desc',
            'content' => 'Full content',
            'status' => 1,
        ]);
        $response = $this->getJson('/api/v1/posts/' . $post->id);
        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Sample post']);
    }

    public function test_authenticated_user_can_update_post()
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
        $create = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/posts/new', [
                'title' => 'Sample Post',
                'short' => 'Short desc',
                'content' => 'Full content',
            ]);
        $postId = $create->json('data.id') ?? $create->json('id');
        $update = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/posts/' . $postId, [
                'title' => 'Sample Post Updated',
                'short' => 'Short desc updated',
                'content' => 'Full content updated',
            ]);
        $update->assertStatus(200)
            ->assertJsonFragment(['title' => 'Sample post updated']);
    }
}
