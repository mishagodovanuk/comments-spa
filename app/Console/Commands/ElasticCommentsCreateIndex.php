<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Elastic\ElasticCommentIndexer;

/**
 * ElasticCommentsCreateIndex command.
 */
final class ElasticCommentsCreateIndex extends Command
{
    /**
     * Command signature.
     *
     * @var string
     */
    protected $signature = 'elastic:comments-create-index {--force : Delete index if exists}';

    /**
     * Command description.
     *
     * @var string
     */
    protected $description = 'Create Elasticsearch index + alias for comments';

    /**
     * Main handle function.
     *
     * @return int
     */
    public function handle(): int
    {
        $indexer = ElasticCommentIndexer::fromConfig();
        $index = (string) config('elastic.index');
        $alias = (string) config('elastic.alias');

        try {
            if ($this->option('force')) {
                if ($indexer->deleteIndex($index)) {
                    $this->info("Deleted index (if existed): {$index}");
                } else {
                    $this->warn("Could not delete index or already not present: {$index}");
                }
            }

            if ($indexer->existsIndex($index)) {
                $this->warn("Index already exists: {$index}");
                $indexer->ensureAlias($index, $alias);

                return self::SUCCESS;
            }

            $body = [
                'settings' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,
                    'analysis' => [
                        'normalizer' => [
                            'lc' => [
                                'type' => 'custom',
                                'filter' => ['lowercase', 'asciifolding'],
                            ],
                        ],
                    ],
                ],
                'mappings' => [
                    'dynamic' => 'strict',
                    'properties' => [
                        'id' => ['type' => 'long'],
                        'parent_id' => ['type' => 'long'],
                        'is_root' => ['type' => 'boolean'],
                        'user_name' => ['type' => 'keyword', 'normalizer' => 'lc'],
                        'email' => ['type' => 'keyword', 'normalizer' => 'lc'],
                        'text_raw' => ['type' => 'text'],
                        'created_at' => ['type' => 'date'],
                    ],
                ],
            ];

            if (!$indexer->createIndex($body, $index)) {
                $this->error("Failed to create index {$index}");

                return self::FAILURE;
            }

            $this->info("Index created: {$index}");
            $indexer->ensureAlias($index, $alias);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Elasticsearch error: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
