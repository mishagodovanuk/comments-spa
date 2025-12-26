<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 *
 */
final class Comment extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'parent_id',
        'user_name',
        'email',
        'home_page',
        'text_html',
        'text_raw',
        'attachment_type',
        'attachment_path',
        'attachment_original_name',
        'ip',
        'user_agent',
    ];

    /**
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
