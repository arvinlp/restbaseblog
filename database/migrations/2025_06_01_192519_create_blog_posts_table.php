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
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('author_id')
                ->nullable()
                ->constrained(
                    table: 'users',
                    column: 'id',
                    indexName: 'posts_author_id'
                )
                ->onDelete('cascade');

            $table->string('title')->nullable();
            $table->text('short')->nullable();
            $table->text('content')->nullable();
            $table->string('thumb')->nullable();
            $table->tinyInteger('status')->default(1)->comment('draft,publish,archive,scheduled');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropForeign('posts_author_id');
        });
        Schema::dropIfExists('blog_posts');
    }
};
