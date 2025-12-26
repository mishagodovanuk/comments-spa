<?php

namespace Tests\Feature\Api\Comments;

use Tests\TestCase;

final class CaptchaTest extends TestCase
{
    public function test_captcha_issues_token_and_challenge(): void
    {
        $res = $this->getJson('/api/captcha');

        $res->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure(['token', 'challenge'])
            ->assertJson(fn ($json) => $json
                ->whereType('token', 'string')
                ->whereType('challenge', 'string')
                ->etc()
            );

        $token = $res->json('token');
        $challenge = $res->json('challenge');

        $this->assertNotEmpty($token);
        $this->assertLessThanOrEqual(64, strlen($token));

        $this->assertNotEmpty($challenge);
        $this->assertSame(6, strlen($challenge));
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $challenge);
    }

    public function test_captcha_is_not_static_between_calls(): void
    {
        $a = $this->getJson('/api/captcha')->assertOk()->json();
        $b = $this->getJson('/api/captcha')->assertOk()->json();

        $this->assertNotSame($a['token'], $b['token']);
        $this->assertNotSame($a['challenge'], $b['challenge']);
    }
}
