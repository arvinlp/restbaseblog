<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blog_post_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')
                ->constrained(
                    table: 'blog_posts',
                    column: 'id',
                    indexName: 'blog_post_id'
                )
                ->onDelete('cascade');
            $table->foreignId('category_id')
                ->constrained(
                    table: 'blog_categories',
                    column: 'id',
                    indexName: 'post_category_id'
                )
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_post_categories');
    }
};
