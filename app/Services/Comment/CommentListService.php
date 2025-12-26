<?php

namespace App\Services\Comment;

use App\Models\Comment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * CommentListService.
 */
final class CommentListService
{
    /**
     * Get roots comments.
     *
     * @param int $page
     * @param string $sort
     * @param string $dir
     * @return LengthAwarePaginator
     */
    public function roots(int $page, string $sort, string $dir): LengthAwarePaginator
    {
        $sort = in_array($sort, ['user_name', 'email', 'created_at'], true) ? $sort : 'created_at';
        $dir = strtolower($dir) === 'asc' ? 'asc' : 'desc';

        $key = "comments:roots:p{$page}:s{$sort}:d{$dir}";

        return Cache::tags(['comments'])->remember($key, now()->addSeconds(30), function () use ($sort, $dir) {
            return Comment::query()
                ->whereNull('parent_id')
                ->orderBy($sort, $dir)
                ->paginate(25);
        });
    }

    /**
     * Get list of comments.
     *
     * @param int $page
     * @param string $sort
     * @param string $direction
     * @return array
     */
    public function list(int $page, string $sort, string $direction): array
    {
        $roots = $this->roots($page, $sort, $direction);
        $rootIds = $roots->getCollection()->pluck('id')->all();
        $descendants = $this->descendantsFlat($rootIds);

        return [
            'roots' => $roots,
            'descendants' => $descendants,
        ];
    }

    /**
     * Get descendants with caching.
     *
     * @param array $rootIds
     * @return Collection
     */
    public function descendantsFlat(array $rootIds): Collection
    {
        $rootIds = array_values(array_filter(array_map('intval', $rootIds)));

        if (empty($rootIds)) {
            return collect();
        }

        sort($rootIds);
        $hash = sha1(implode(',', $rootIds));

        $key = "comments:descendants:{$hash}";

        return Cache::tags(['comments'])->remember($key, now()->addSeconds(30), function () use ($rootIds) {
            $all = collect();
            $frontier = $rootIds;

            while (!empty($frontier)) {
                $chunk = Comment::query()
                    ->whereIn('parent_id', $frontier)
                    ->get();

                if ($chunk->isEmpty()) {
                    break;
                }

                $all = $all->concat($chunk);
                $frontier = $chunk->pluck('id')->all();
            }

            return $all->values();
        });
    }
}
