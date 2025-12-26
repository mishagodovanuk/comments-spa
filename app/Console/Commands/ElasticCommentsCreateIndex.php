<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

final class ElasticCommentsCreateIndex extends Command
{
    protected $signature = 'elastic:comments-create-index {--force : Delete index if exists}';
    protected $description = 'Create Elasticsearch index + alias for comments (scalable, versioned index) using curl (file body, stable)';

    public function handle(): int
    {
        $host  = rtrim((string) config('elastic.host'), '/');
        $index = (string) config('elastic.index');
        $alias = (string) config('elastic.alias');

        if ($this->option('force')) {
            $this->curl('DELETE', "{$host}/{$index}");
            $this->info("Deleted index (if existed): {$index}");
        }

        if ($this->existsIndex($host, $index)) {
            $this->warn("Index already exists: {$index}");
            $this->ensureAlias($host, $index, $alias);

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

        $json = json_encode($body, JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            $this->error('Failed to json_encode index body.');

            return self::FAILURE;
        }

        $res = $this->curl('PUT', "{$host}/{$index}", $json);

        if ($res['exit_code'] !== 0) {
            $this->error("Failed to create index {$index} (curl exit {$res['exit_code']})");
            $this->line($res['stderr'] ?: $res['stdout']);

            return self::FAILURE;
        }

        if (!$this->existsIndex($host, $index)) {
            $this->error("Index was not created (exists check failed): {$index}");
            $this->line($res['stdout']);

            return self::FAILURE;
        }

        $this->info("Index created: {$index}");
        $this->ensureAlias($host, $index, $alias);

        return self::SUCCESS;
    }

    private function ensureAlias(string $host, string $index, string $alias): void
    {
        if ($alias === '') {
            $this->warn('ELASTIC_ALIAS is empty, skipping alias setup.');

            return;
        }

        $actions = [
            'actions' => [
                ['remove' => ['index' => '*', 'alias' => $alias]],
                ['add' => ['index' => $index, 'alias' => $alias]],
            ],
        ];

        $json = json_encode($actions, JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            $this->warn('Failed to json_encode alias actions.');

            return;
        }

        $res = $this->curl('POST', "{$host}/_aliases", $json);

        if ($res['exit_code'] === 0) {
            $this->info("Alias '{$alias}' -> {$index}");
        } else {
            $this->warn("Failed to set alias '{$alias}' (curl exit {$res['exit_code']})");
            $this->line($res['stderr'] ?: $res['stdout']);
        }
    }

    private function existsIndex(string $host, string $index): bool
    {
        $p = new Process([
            'curl',
            '-sS',
            '-o', '/dev/null',
            '-w', '%{http_code}',
            '--connect-timeout', '3',
            '--max-time', '20',
            '-I',
            "{$host}/{$index}",
        ]);

        $p->run();
        $code = trim($p->getOutput());

        return $p->getExitCode() === 0 && $code === '200';
    }

    private function curl(string $method, string $url, ?string $jsonBody = null): array
    {
        $tmpFile = null;

        try {
            $cmd = [
                'curl',
                '-sS',
                '--http1.1',
                '--connect-timeout', '3',
                '--max-time', '20',
                '-X', $method,
                $url,
            ];

            if ($jsonBody !== null) {
                $tmpFile = tempnam(sys_get_temp_dir(), 'es_');

                if ($tmpFile === false) {
                    return [
                        'exit_code' => 1,
                        'stdout' => '',
                        'stderr' => 'tempnam() failed',
                    ];
                }

                file_put_contents($tmpFile, $jsonBody);

                $cmd[] = '-H';
                $cmd[] = 'Content-Type: application/json';
                $cmd[] = '--data-binary';
                $cmd[] = '@' . $tmpFile; // IMPORTANT: file upload (Content-Length ok, no chunked)
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
