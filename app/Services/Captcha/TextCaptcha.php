<?php

namespace App\Services\Captcha;

use App\Models\Captcha;
use Illuminate\Support\Str;

final class TextCaptcha
{
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
