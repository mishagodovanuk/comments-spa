<?php

namespace Tests\Feature\Api\Comments;

use App\Http\Requests\CommentStoreRequest;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class CommentStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_store_required_fields(): void
    {
        $res = $this->postJson('/api/comments', []);

        $res->assertStatus(422)
            ->assertJsonValidationErrors([
                'user_name',
                'email',
                'captcha_token',
                'captcha_answer',
                'text',
            ]);
    }

    public function test_store_invalid_user_name(): void
    {
        $res = $this->postJson('/api/comments', [
            'user_name' => 'bad name!',
            'email' => 'test@test.com',
            'text' => 'hello',
            'captcha_token' => 'token',
            'captcha_answer' => '123',
        ]);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['user_name']);
    }

    public function test_store_sql_injection_user_name(): void
    {
        $res = $this->postJson('/api/comments', [
            'user_name' => "admin'--",
            'email' => 'test@test.com',
            'text' => 'hello',
            'captcha_token' => 'token',
            'captcha_answer' => '123',
        ]);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['user_name']);
    }

    public function test_store_invalid_email(): void
    {
        $res = $this->postJson('/api/comments', [
            'user_name' => 'User123',
            'email' => 'not-an-email',
            'text' => 'hello',
            'captcha_token' => 'token',
            'captcha_answer' => '123',
        ]);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_store_invalid_home_page_url(): void
    {
        $res = $this->postJson('/api/comments', [
            'user_name' => 'User123',
            'email' => 'test@test.com',
            'home_page' => 'not-a-url',
            'text' => 'hello',
            'captcha_token' => 'token',
            'captcha_answer' => '123',
        ]);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['home_page']);
    }

    public function test_store_too_long_text(): void
    {
        $res = $this->postJson('/api/comments', [
            'user_name' => 'User123',
            'email' => 'test@test.com',
            'text' => str_repeat('a', 5001),
            'captcha_token' => 'token',
            'captcha_answer' => '123',
        ]);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['text']);
    }

    public function test_parent_id_must_exist_validation(): void
    {
        $rules = (new CommentStoreRequest())->rules();

        $validator = Validator::make([
            'parent_id' => 999999,
        ], [
            'parent_id' => $rules['parent_id'],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('parent_id', $validator->errors()->toArray());
    }

    public function test_parent_id_existing_passes_validation(): void
    {
        $parentId = $this->insertCommentRow('Parent1');

        $rules = (new CommentStoreRequest())->rules();

        $validator = Validator::make([
            'parent_id' => $parentId,
        ], [
            'parent_id' => $rules['parent_id'],
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_store_comment_with_captcha(): void
    {
        $captcha = $this->getJson('/api/captcha')->assertOk()->json();

        $payload = [
            'user_name' => 'User123',
            'email' => 'test@test.com',
            'home_page' => 'https://example.com',
            'text' => 'hello',
            'captcha_token' => $captcha['token'],
            'captcha_answer' => $captcha['challenge'],
        ];

        $res = $this->postJson('/api/comments', $payload);

        $res->assertCreated()
            ->assertJsonStructure(['id']);

        $this->assertDatabaseHas('comments', [
            'id' => $res->json('id'),
            'user_name' => 'User123',
            'email' => 'test@test.com',
        ]);
    }

    private function insertCommentRow(string $userName): int
    {
        return (int) DB::table('comments')->insertGetId([
            'parent_id' => null,
            'user_name' => $userName,
            'email' => strtolower($userName) . '@test.com',
            'home_page' => null,
            'text_html' => '<p>seed</p>',
            'attachment_path' => null,
            'attachment_original_name' => null,
            'attachment_type' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
