<?php

namespace App\Services\Elastic;

use App\Models\Comment;
use Symfony\Component\Process\Process;

/**
 * ElasticCommentIndexer.
 *
 * Elastic service.
 */
final class ElasticCommentIndexer
{
    /**
     * @param string $host
     * @param string $alias
     */
    public function __construct(
        private readonly string $host,
        private readonly string $alias,
    ) {}

    /**
     * Return self instance with config.
     *
     * @return self
     */
    public static function fromConfig(): self
    {
        return new self(
            rtrim((string) config('elastic.host'), '/'),
            (string) config('elastic.alias'),
        );
    }

    /**
     * Index comment.
     *
     * @param Comment $comment
     * @return void
     */
    public function indexComment(Comment $comment): void
    {
        $doc = [
            'id' => (int) $comment->id,
            'parent_id' => $comment->parent_id ? (int) $comment->parent_id : null,
            'is_root' => $comment->parent_id === null,

            'user_name' => (string) $comment->user_name,
            'email' => (string) $comment->email,
            'text_raw' => (string) $comment->text_raw,

            'created_at' => $comment->created_at?->toIso8601String(),
        ];

        $json = json_encode($doc, JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new \RuntimeException('Failed to json_encode comment doc for ES.');
        }

        $index = $this->indexName();
        $url = "{$this->host}/{$index}/_doc/{$comment->id}";

        $res = $this->curl('PUT', $url, $json);

        if ($res['exit_code'] !== 0) {
            throw new \RuntimeException('Elasticsearch index failed: ' . ($res['stderr'] ?: $res['stdout']));
        }
    }

    /**
     * Delete comment.
     *
     * Currently delete functionality not used, only in --force command.
     *
     * @param int $id
     * @return void
     */
    public function deleteComment(int $id): void
    {
        $index = $this->indexName();
        $url = "{$this->host}/{$index}/_doc/{$id}";

        $res = $this->curl('DELETE', $url, null);

        if ($res['exit_code'] !== 0) {
            throw new \RuntimeException('Elasticsearch delete failed: ' . ($res['stderr'] ?: $res['stdout']));
        }
    }

    /**
     * Search comments.
     */
    public function search(string $q, int $page = 1, int $perPage = 20): array
    {
        $q = trim($q);
        $page = max(1, $page);
        $perPage = max(1, min(200, $perPage));

        $from = ($page - 1) * $perPage;

        $qLower = mb_strtolower($q);
        $wc = '*' . $this->escapeWildcard($qLower) . '*';

        $body = [
            'track_total_hits' => true,
            'from' => $from,
            'size' => $perPage,
            'sort' => [
                ['created_at' => ['order' => 'desc']],
                ['id' => ['order' => 'desc']],
            ],
        ];

        if ($q === '') {
            $body['query'] = ['match_all' => (object) []];
        } else {
            $body['query'] = [
                'bool' => [
                    'should' => [
                        // analyzed text search
                        ['match' => ['text_raw' => ['query' => $q]]],

                        // keyword substring search (works with your mapping)
                        ['wildcard' => ['email' => ['value' => $wc]]],
                        ['wildcard' => ['user_name' => ['value' => $wc]]],
                    ],
                    'minimum_should_match' => 1,
                ],
            ];
        }

        $json = json_encode($body, JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new \RuntimeException('Failed to json_encode search query for ES.');
        }

        $index = $this->indexName();
        $url = "{$this->host}/{$index}/_search";

        $res = $this->curl('POST', $url, $json);

        if ($res['exit_code'] !== 0) {
            throw new \RuntimeException('Elasticsearch search failed: ' . ($res['stderr'] ?: $res['stdout']));
        }

        $data = json_decode((string) $res['stdout'], true);

        if (!is_array($data)) {
            throw new \RuntimeException('Elasticsearch search failed: invalid JSON response.');
        }

        $hits = $data['hits']['hits'] ?? [];
        $total = (int) (($data['hits']['total']['value'] ?? 0));

        $items = [];

        foreach ($hits as $hit) {
            if (isset($hit['_source']) && is_array($hit['_source'])) {
                $items[] = $hit['_source'];
            }
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * Get index name.
     *
     * @return string
     */
    private function indexName(): string
    {
        return $this->alias !== '' ? $this->alias : (string) config('elastic.index');
    }

    /**
     * Escape characters.
     */
    private function escapeWildcard(string $value): string
    {
        return str_replace(
            ['\\', '*', '?'],
            ['\\\\', '\\*', '\\?'],
            $value
        );
    }

    /**
     * Curl.
     *
     * @param string $method
     * @param string $url
     * @param string|null $jsonBody
     * @return array
     */
    private function curl(string $method, string $url, ?string $jsonBody): array
    {
        $tmpFile = null;

        try {
            $cmd = [
                'curl',
                '-sS',
                '--http1.1',
                '--connect-timeout', '3',
                '--max-time', '10',
                '-X', $method,
                $url,
            ];

            if ($jsonBody !== null) {
                $tmpFile = tempnam(sys_get_temp_dir(), 'es_');

                if ($tmpFile === false) {
                    return ['exit_code' => 1, 'stdout' => '', 'stderr' => 'tempnam() failed'];
                }

                file_put_contents($tmpFile, $jsonBody);

                $cmd[] = '-H';
                $cmd[] = 'Content-Type: application/json';
                $cmd[] = '--data-binary';
                $cmd[] = '@' . $tmpFile;
            }

            $p = new Process($cmd);
            $p->run();

            return [
                'exit_code' => $p->getExitCode() ?? 1,
                'stdout' => $p->getOutput(),
                'stderr' => $p->getErrorOutput(),
            ];
        } finally {
            if ($tmpFile && is_file($tmpFile)) {
                @unlink($tmpFile);
            }
        }
    }

    /**
     * Check if index exists.
     */
    public function existsIndex(string $index = null): bool
    {
        $index ??= (string) config('elastic.index');
        $url = "{$this->host}/{$index}";
        $cmd = [
            'curl',
            '-sS',
            '-o', '/dev/null',
            '-w', '%{http_code}',
            '--connect-timeout', '3',
            '--max-time', '20',
            '-I',
            $url
        ];
        $p = new Process($cmd);
        $p->run();
        $code = trim($p->getOutput());

        return $p->getExitCode() === 0 && $code === '200';
    }

    /**
     * Create index with given body.
     */
    public function createIndex(array $body, string $index = null): bool
    {
        $index ??= (string) config('elastic.index');
        $url = "{$this->host}/{$index}";
        $json = json_encode($body, JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }
        $res = $this->curl('PUT', $url, $json);

        return $res['exit_code'] === 0 && $this->existsIndex($index);
    }

    /**
     * Delete index.
     */
    public function deleteIndex(string $index = null): bool
    {
        $index ??= (string) config('elastic.index');
        $url = "{$this->host}/{$index}";
        $res = $this->curl('DELETE', $url, null);

        return $res['exit_code'] === 0;
    }

    /**
     * Ensure alias points to correct index.
     */
    public function ensureAlias(string $index = null, string $alias = null): bool
    {
        $index ??= (string) config('elastic.index');
        $alias ??= $this->alias;

        if ($alias === '') {
            return false;
        }

        $actions = [
            'actions' => [
                ['remove' => ['index' => '*', 'alias' => $alias]],
                ['add' => ['index' => $index, 'alias' => $alias]],
            ],
        ];

        $json = json_encode($actions, JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            return false;
        }

        $url = "{$this->host}/_aliases";
        $res = $this->curl('POST', $url, $json);

        return $res['exit_code'] === 0;
    }
}
