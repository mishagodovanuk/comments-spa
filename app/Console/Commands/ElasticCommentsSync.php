<?php

namespace App\Console\Commands;

use App\Models\Comment;
use App\Services\Elastic\ElasticCommentIndexer;
use Illuminate\Console\Command;

final class ElasticCommentsSync extends Command
{
    protected $signature = 'elastic:comments-sync {--chunk=200} {--from-id=1} {--to-id=}';
    protected $description = 'Backfill: index existing comments into Elasticsearch (alias comments)';

    public function handle(): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $fromId = max(1, (int) $this->option('from-id'));
        $toIdRaw = $this->option('to-id');
        $toId = $toIdRaw !== null ? (int) $toIdRaw : null;

        $indexer = ElasticCommentIndexer::fromConfig();

        $q = Comment::query()
            ->where('id', '>=', $fromId)
            ->orderBy('id');

        if ($toId !== null && $toId > 0) {
            $q->where('id', '<=', $toId);
        }

        $total = (clone $q)->count();

        $this->info(
            "Sync comments to ES. total={$total}, chunk={$chunk}, from_id={$fromId}" .
            ($toId ? ", to_id={$toId}" : '')
        );

        if ($total === 0) {
            $this->warn('Nothing to sync.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $q->chunkById($chunk, function ($items) use ($indexer, $bar) {
            foreach ($items as $comment) {
                $indexer->indexComment($comment);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }
}
