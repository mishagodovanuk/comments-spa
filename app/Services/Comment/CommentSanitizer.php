<?php

namespace App\Services\Comment;

use Mews\Purifier\Facades\Purifier;

/**
 * CommentSanitizer.
 */
final class CommentSanitizer
{
    /**
     * Public sanitize function.
     *
     * @param string $raw
     * @return string
     */
    public function sanitize(string $raw): string
    {
        return Purifier::clean($raw, 'default');
    }
}
