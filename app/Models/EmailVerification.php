<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailVerification extends Model
{
    protected $fillable = [
        'email',
        'code',
        'status',
        'created_at',
    ];

    public $timestamps = false;
}
