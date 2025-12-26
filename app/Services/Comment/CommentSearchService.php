<?php

namespace App\Services\Comment;

use App\Services\Elastic\ElasticCommentIndexer;

final class CommentSearchService
{
    /**
     * Fulltext search.
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

        $elastic = ElasticCommentIndexer::fromConfig();
        $result = $elastic->search($q, $page, $perPage);

        $items = $result['items'] ?? [];
        $total = (int) ($result['total'] ?? 0);

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
