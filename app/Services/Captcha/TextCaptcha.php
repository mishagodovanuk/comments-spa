<?php

namespace App\Services\Captcha;

use App\Models\Captcha;
use Illuminate\Support\Str;

/**
 * TextCaptcha.
 */
final class TextCaptcha
{
    /**
     * Return captcha.
     *
     * @return array
     */
    public function issue(): array
    {
        $token = Str::random(32);
        $value = strtoupper(substr(Str::random(6), 0, 6));

        Captcha::create([
            'token' => $token,
            'value' => $value,
            'expires_at' => now()->addMinutes(10),
        ]);

        return ['token' => $token, 'challenge' => $value];
    }

    /**
     * Verify captcha.
     *
     * @param string $token
     * @param string $answer
     * @return void
     */
    public function verify(string $token, string $answer): void
    {
        $row = Captcha::query()
            ->where('token', $token)
            ->where('expires_at', '>=', now())
            ->first();

        if (!$row) {
            throw new \RuntimeException('CAPTCHA expired or invalid.');
        }

        if (strtoupper(trim($answer)) !== strtoupper($row->value)) {
            throw new \RuntimeException('CAPTCHA incorrect.');
        }

        $row->delete();
    }
}
