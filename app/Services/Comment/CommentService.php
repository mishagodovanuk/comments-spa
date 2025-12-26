<?php

namespace App\Services\Comment;

use App\Events\CommentCreated;
use App\Models\Comment;
use App\Services\Captcha\TextCaptcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Jobs\IndexCommentToElastic;

/**
 * CommentService.
 *
 * Used for storing and preview comments.
 */
final class CommentService
{
    /**
     * @param CommentSanitizer $sanitizer
     * @param AttachmentService $attachments
     * @param TextCaptcha $captcha
     */
    public function __construct(
        private readonly CommentSanitizer $sanitizer,
        private readonly AttachmentService $attachments,
        private readonly TextCaptcha $captcha,
    ) {}

    /**
     * Preview comment.
     *
     * @param string $text
     * @return string
     */
    public function preview(string $text): string
    {
        $raw = (string) $text;

        return $this->sanitizer->sanitize($raw);
    }

    /**
     * Create comment.
     *
     * Used elastic and brodcast chanel.
     *
     * @param array $data
     * @param Request $request
     * @return Comment
     * @throws Throwable
     */
    public function create(array $data, Request $request): Comment
    {
        $this->captcha->verify((string) $data['captcha_token'], (string) $data['captcha_answer']);

        $raw = (string) $data['text'];
        $clean = $this->sanitizer->sanitize($raw);

        try {
            $comment = DB::transaction(function () use ($data, $request, $raw, $clean) {
                $att = $this->attachments->handle($request->file('file'));

                return Comment::create([
                    'parent_id' => $data['parent_id'] ?? null,
                    'user_name' => $data['user_name'],
                    'email' => $data['email'],
                    'home_page' => $data['home_page'] ?? null,
                    'text_html' => $clean,
                    'text_raw' => $raw,

                    'attachment_type' => $att['type'],
                    'attachment_path' => $att['path'],
                    'attachment_original_name' => $att['original'],

                    'ip' => (string) $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 255),
                ]);
            });

            Cache::tags(['comments'])->flush();
            event(new CommentCreated($comment->id, $comment->parent_id));
            IndexCommentToElastic::dispatch($comment->id);

            return $comment;
        } catch (Throwable $e) {
            Log::error('Comment create failed', [
                'error' => $e->getMessage(),
                'user_name' => $data['user_name'] ?? null,
                'email' => $data['email'] ?? null,
            ]);
            throw $e;
        }
    }
}
