<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BlogPost extends Model
{
    //
    use HasFactory, SoftDeletes;

    protected $hidden = [];

    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ucfirst($value),
            set: fn ($value) => strtolower($value),
        );
    }

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value)->diffForHumans(),
            set: fn ($value) => $value,
        );
    }

    protected function updatedAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value)->diffForHumans(),
            set: fn ($value) => $value,
        );
    }

    protected function thumb(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? 'No Image',
            set: fn ($value) => $value,
        );
    }

    public function author()
    {
        return $this->hasOne(User::class, 'id', 'author_id')->select(['id', 'nickname', 'first_name', 'last_name']);
    }

    public function categories()
    {
        return $this->belongsToMany(BlogCategory::class, 'blog_post_categories', 'post_id', 'category_id')->where('status',1);
    }
}
