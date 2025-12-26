<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CommentListResource.
 */
final class CommentListResource extends JsonResource
{
    /**
     * @param $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'roots' => CommentResource::collection(
                $this->resource['roots']->getCollection()
            ),
            'descendants_flat' => CommentResource::collection(
                $this->resource['descendants']
            ),
            'meta' => [
                'current_page' => $this->resource['roots']->currentPage(),
                'per_page' => $this->resource['roots']->perPage(),
                'total' => $this->resource['roots']->total(),
                'last_page' => $this->resource['roots']->lastPage(),
            ],
        ];
    }
}
