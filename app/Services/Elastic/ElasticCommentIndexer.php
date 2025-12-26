<?php

namespace App\Services\Elastic;

use App\Models\Comment;
use App\Jobs\IndexCommentToElastic;
use Symfony\Component\Process\Process;

final class ElasticCommentIndexer
{
    public function __construct(
        private readonly string $host,
        private readonly string $alias,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            rtrim((string) config('elastic.host'), '/'),
            (string) config('elastic.alias'),
        );
    }

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

        $index = $this->alias !== '' ? $this->alias : (string) config('elastic.index');

        $url = "{$this->host}/{$index}/_doc/{$comment->id}";

        $res = $this->curl('PUT', $url, $json);

        if ($res['exit_code'] !== 0) {
            throw new \RuntimeException('Elasticsearch index failed: ' . ($res['stderr'] ?: $res['stdout']));
        }
    }

    public function deleteComment(int $id): void
    {
        $index = $this->alias !== '' ? $this->alias : (string) config('elastic.index');
        $url = "{$this->host}/{$index}/_doc/{$id}";

        $res = $this->curl('DELETE', $url, null);

        if ($res['exit_code'] !== 0) {
            throw new \RuntimeException('Elasticsearch delete failed: ' . ($res['stderr'] ?: $res['stdout']));
        }
    }

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
}
