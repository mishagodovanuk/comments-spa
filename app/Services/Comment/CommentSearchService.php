<?php

namespace App\Services\Comment;

use Illuminate\Support\Facades\Http;

final class CommentSearchService
{
    /**
     * Fulltext search via Elasticsearch (alias "comments").
     *
     * Returns:
     *  [
     *    'items' => [...],
     *    'meta' => [...]
     *  ]
     */
    public function search(string $q, int $page = 1, int $perPage = 20): array
    {
        $q = trim($q);

        if (mb_strlen($q) < 2) {
            return [
                'items' => [],
                'meta' => [
                    'q' => $q,
                    'current_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'last_page' => 0,
                ],
            ];
        }

        $page = max(1, $page);
        $perPage = min(50, max(1, $perPage));
        $from = ($page - 1) * $perPage;

        $host = rtrim((string) config('elastic.host'), '/');
        $alias = (string) config('elastic.alias'); // comments

        $body = [
            'from' => $from,
            'size' => $perPage,
            'sort' => [
                ['created_at' => ['order' => 'desc']],
            ],
            'query' => [
                'multi_match' => [
                    'query' => $q,
                    'fields' => ['text_raw', 'user_name', 'email'],
                    'type' => 'best_fields',
                    'operator' => 'and',
                ],
            ],
            'highlight' => [
                'pre_tags' => ['<mark>'],
                'post_tags' => ['</mark>'],
                'fields' => [
                    'text_raw' => (object)[],
                ],
            ],
        ];

        $res = Http::asJson()
            ->timeout(10)
            ->post("{$host}/{$alias}/_search", $body);

        if (!$res->ok()) {
            return [
                'error' => true,
                'status' => $res->status(),
                'body' => $res->json(),
            ];
        }

        $json = $res->json();
        $hits = $json['hits']['hits'] ?? [];
        $total = (int) (($json['hits']['total']['value'] ?? 0));

        $items = array_map(static function (array $h): array {
            $src = $h['_source'] ?? [];
            $hl = $h['highlight']['text_raw'][0] ?? null;

            return [
                'id' => $src['id'] ?? null,
                'parent_id' => $src['parent_id'] ?? null,
                'is_root' => $src['is_root'] ?? null,
                'user_name' => $src['user_name'] ?? null,
                'email' => $src['email'] ?? null,
                'text_raw' => $src['text_raw'] ?? null,
                'text_highlight' => $hl,
                'created_at' => $src['created_at'] ?? null,
            ];
        }, $hits);

        return [
            'items' => $items,
            'meta' => [
                'q' => $q,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ];
    }
}
