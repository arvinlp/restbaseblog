<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\BlogCategory;
use App\Models\EmailVerification;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_category()
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
            ->postJson('/api/v1/categories/new', [
                'name' => 'Sample Category',
                'content' => 'Category content',
                'parent_id' => null,
            ]);
        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'data']);
    }

    public function test_anyone_can_view_category()
    {
        $category = BlogCategory::factory()->create([
            'name' => 'Sample Category',
            'content' => 'Category content',
            'status' => 1,
        ]);
        $response = $this->getJson('/api/v1/categories/' . $category->id);
        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Sample Category']);
    }

    public function test_authenticated_user_can_update_category()
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
            ->postJson('/api/v1/categories/new', [
                'name' => 'Sample Category',
                'content' => 'Category content',
                'parent_id' => null,
            ]);
        $categoryId = $create->json('data.id') ?? $create->json('id');
        $update = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/categories/' . $categoryId, [
                'name' => 'Sample Category Updated',
                'content' => 'Updated content',
                'parent_id' => null,
            ]);
        $update->assertStatus(200)
            ->assertJsonFragment(['name' => 'Sample Category Updated']);
    }
}
