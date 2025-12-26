<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Captcha.
 */
final class Captcha extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = ['token', 'value', 'expires_at'];

    /**
     * @var string[]
     */
    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
