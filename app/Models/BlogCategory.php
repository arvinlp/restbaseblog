<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BlogCategory extends Model
{
    use HasFactory, SoftDeletes;
    public $timestamps = false;

    protected $hidden = [
        'content',
        'deleted_at',
    ];

    public function parent()
    {
        return $this->hasOne(BlogCategory::class, 'id', 'parent_id')->select('id', 'name')->where('status', 1);
    }

    public function children()
    {
        return $this->hasMany(BlogCategory::class, 'parent_id', 'id')->select('id', 'parent_id', 'name')->where('status', 1);
    }

    public function posts()
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_categories', 'category_id', 'post_id');
    }
}
