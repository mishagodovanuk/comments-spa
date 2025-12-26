<?php

namespace App\Services\Comment;

use HTMLPurifier;
use HTMLPurifier_Config;

final class CommentSanitizer
{
    private HTMLPurifier $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'a[href|title],strong,i,code');
        $config->set('HTML.SafeIframe', false);
        $config->set('HTML.SafeObject', false);
        $config->set('HTML.SafeEmbed', false);
        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
        ]);
        $config->set('CSS.AllowedProperties', []);
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('AutoFormat.AutoParagraph', false);

        $this->purifier = new HTMLPurifier($config);
    }

    public function sanitize(string $raw): string
    {
        return $this->purifier->purify($raw);
    }
}
