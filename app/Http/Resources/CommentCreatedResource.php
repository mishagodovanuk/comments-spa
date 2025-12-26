<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

final class CommentCreatedResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id];
    }
}
