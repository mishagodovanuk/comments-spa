<?php

namespace App\Http\Resources;

use App\Models\Comment;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CommentSearchResource.
 */
final class CommentSearchResource extends JsonResource
{
    /**
     * @param $request
     * @return array
     */
    public function toArray($request): array
    {
        $items = $this->resource['items'] ?? [];

        $ids = array_filter(array_map(function ($item) {
            return $item['id'] ?? null;
        }, $items));

        $comments = empty($ids)
            ? collect()
            : Comment::query()
                ->whereIn('id', $ids)
                ->get()
                ->keyBy('id');

        $orderedComments = collect($items)
            ->map(function ($item) use ($comments) {
                $id = $item['id'] ?? null;
                return $id ? $comments->get($id) : null;
            })
            ->filter();

        return [
            'items' => CommentResource::collection($orderedComments),
            'meta' => $this->resource['meta'] ?? [],
        ];
    }
}

