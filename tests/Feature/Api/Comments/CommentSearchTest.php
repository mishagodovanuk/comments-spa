<?php

namespace Tests\Feature\Api\Comments;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CommentSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_requires_q(): void
    {
        $res = $this->getJson('/api/comments/search');

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    public function test_search_short_q(): void
    {
        $res = $this->getJson('/api/comments/search?q=a');

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    public function test_search_too_long_q(): void
    {
        $res = $this->getJson('/api/comments/search?q=' . str_repeat('a', 201));

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    public function test_search_page_positive_integer(): void
    {
        $res = $this->getJson('/api/comments/search?q=test&page=0');

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['page']);
    }

    public function test_search_per_page_limitation(): void
    {
        $res = $this->getJson('/api/comments/search?q=test&per_page=1000');

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_search_returns_json_object(): void
    {
        $res = $this->getJson('/api/comments/search?q=test');
        $res->assertOk()
            ->assertHeader('Content-Type', 'application/json');
        $json = $res->json();
        $this->assertIsArray($json);

        $this->assertArrayNotHasKey('errors', $json);

    }

    public function test_search_sql_injection(): void
    {
        $payload = urlencode("' OR 1=1 --");
        $res = $this->getJson("/api/comments/search?q={$payload}");
        $res->assertOk();

        $this->assertIsArray($res->json());
    }
}
