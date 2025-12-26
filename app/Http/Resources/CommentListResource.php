<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

final class CommentListResource extends JsonResource
{
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
