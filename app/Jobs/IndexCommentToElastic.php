<?php

namespace App\Jobs;

use App\Models\Comment;
use App\Services\Elastic\ElasticCommentIndexer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * IndexCommentToElastic.
 */
final class IndexCommentToElastic implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int
     */
    public int $tries = 10;
    /**
     * @var int
     */
    public int $backoff = 5;

    /**
     * @param int $commentId
     */
    public function __construct(
        public readonly int $commentId
    ) {}

    /**
     * Add comment to elastic.
     *
     * @return void
     */
    public function handle(): void
    {
        $comment = Comment::query()->find($this->commentId);

        if (!$comment) {
            return;
        }

        ElasticCommentIndexer::fromConfig()->indexComment($comment);
    }
}
