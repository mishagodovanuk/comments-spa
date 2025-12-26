<?php

namespace Tests\Feature\Api\Comments;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CommentPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_requires_text(): void
    {
        $res = $this->postJson('/api/comments/preview', []);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['text']);
    }

    public function test_preview_too_long_text(): void
    {
        $res = $this->postJson('/api/comments/preview', [
            'text' => str_repeat('a', 5001),
        ]);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['text']);
    }

    public function test_preview_returns_html_string_format(): void
    {
        $res = $this->postJson('/api/comments/preview', [
            'text' => 'hello',
        ]);

        $res->assertOk()
            ->assertJsonStructure(['html'])
            ->assertJson(fn ($json) => $json
                ->whereType('html', 'string')
                ->etc()
            );

        $this->assertNotSame('', trim((string) $res->json('html')));
    }

    public function test_preview_escapes_xss(): void
    {
        $payload = implode("\n", [
            '<script>alert(1)</script>',
            '<img src=x onerror=alert(1)>',
            '<a href="javascript:alert(1)">click</a>',
            '<iframe src="javascript:alert(1)"></iframe>',
            '<b>ok</b>',
        ]);

        $res = $this->postJson('/api/comments/preview', [
            'text' => $payload,
        ])->assertOk();

        $html = (string) $res->json('html');

        $this->assertIsString($html);

        $this->assertStringNotContainsString('<script', strtolower($html));
        $this->assertStringNotContainsString('onerror=', strtolower($html));
        $this->assertStringNotContainsString('onclick=', strtolower($html));
        $this->assertStringNotContainsString('javascript:', strtolower($html));
        $this->assertStringNotContainsString('<iframe', strtolower($html));

        $this->assertStringContainsString('ok', $html);
    }
}
