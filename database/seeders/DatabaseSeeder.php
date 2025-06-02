<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogPostCategory;
use App\Models\User;
use Carbon\Carbon;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Factories\Sequence;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'nickname' => 'Test Admin',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'test@example.com',
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make(123456789)
        ]);
        //
        User::factory(10)
            ->has(
                BlogPost::factory()->has(
                    BlogCategory::factory()->has(BlogCategory::factory(2), 'children'),
                    'categories'
                )->count(rand(3, 10)),
                'posts'
            )
            ->create();
    }
}
