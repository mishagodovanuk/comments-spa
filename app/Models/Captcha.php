<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Captcha extends Model
{
    protected $fillable = ['token', 'value', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
