<?php

namespace App\Jobs;

use App\Models\Comment;
use App\Services\Elastic\ElasticCommentIndexer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class IndexCommentToElastic implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 10;
    public int $backoff = 5;

    public function __construct(
        public readonly int $commentId
    ) {}

    public function handle(): void
    {
        $comment = Comment::query()->find($this->commentId);

        if (!$comment) {
            return;
        }

        ElasticCommentIndexer::fromConfig()->indexComment($comment);
    }
}
