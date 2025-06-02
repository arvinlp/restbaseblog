<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class BlogPostCategory extends Pivot
{
    //
    public $timestamps = false;
    public $table = 'blog_post_categories';
}
