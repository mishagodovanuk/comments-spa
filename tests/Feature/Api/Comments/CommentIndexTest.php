<?php

namespace Tests\Feature\Api\Comments;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CommentIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_accepts_default_params_and_returns_items_structure(): void
    {
        $this->seedTwoRootComments();

        $res = $this->getJson('/api/comments');

        $res->assertOk()
            ->assertJsonStructure([
                'roots' => [
                    '*' => [
                        'id',
                        'parent_id',
                        'user_name',
                        'email',
                        'home_page',
                        'text_html',
                        'attachment',
                        'created_at',
                    ],
                ],
                'descendants_flat',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);

        $res->assertJsonPath('meta.current_page', 1);
    }

    public function test_index_invalid_sort_field(): void
    {
        $res = $this->getJson('/api/comments?sort=1 desc');

        $res->assertStatus(422)->assertJsonValidationErrors(['sort']);
    }

    public function test_index_invalid_direction(): void
    {
        $res = $this->getJson('/api/comments?direction=drop table');

        $res->assertStatus(422)->assertJsonValidationErrors(['direction']);
    }

    public function test_index_sql_injection(): void
    {
        $res = $this->getJson('/api/comments?sort=created_at desc;drop table comments;--');

        $res->assertStatus(422);
    }

    public function test_index_paginator(): void
    {
        $res = $this->getJson('/api/comments?page=0');

        $res->assertStatus(422)->assertJsonValidationErrors(['page']);
    }

    public function test_index_sort_user_name_desc(): void
    {
        [$olderId, $newerId] = $this->seedTwoRootComments();

        $res = $this->getJson('/api/comments');
        $res->assertOk();
        $roots = $res->json('roots');

        $this->assertCount(2, $roots);
        $this->assertSame($newerId, $roots[0]['id']);
        $this->assertSame($olderId, $roots[1]['id']);
    }

    public function test_index_sort_user_name_asc(): void
    {
        $idB = $this->insertComment(userName: 'B', createdAt: '2025-01-01 10:00:00');
        $idA = $this->insertComment(userName: 'A', createdAt: '2025-01-02 10:00:00');

        $res = $this->getJson('/api/comments?sort=user_name&direction=asc');

        $res->assertOk();

        $roots = $res->json('roots');
        $this->assertCount(2, $roots);

        $this->assertSame($idA, $roots[0]['id']);
        $this->assertSame($idB, $roots[1]['id']);
    }

    private function seedTwoRootComments(): array
    {
        $olderId = $this->insertComment(userName: 'Old', createdAt: '2025-01-01 10:00:00');
        $newerId = $this->insertComment(userName: 'New', createdAt: '2025-01-02 10:00:00');

        return [$olderId, $newerId];
    }

    private function insertComment(string $userName, string $createdAt): int
    {
        $now = Carbon::parse($createdAt);

        return (int) DB::table('comments')->insertGetId([
            'parent_id' => null,
            'user_name' => $userName,
            'email' => strtolower($userName).'@test.com',
            'home_page' => null,
            'text_html' => '<p>Hello</p>',
            'attachment_path' => null,
            'attachment_original_name' => null,
            'attachment_type' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
