<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * CommentResource.
 */
final class CommentResource extends JsonResource
{
    /**
     * @param $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'user_name' => $this->user_name,
            'email' => $this->email,
            'home_page' => $this->home_page,
            'text_html' => $this->text_html,
            'attachment' => $this->attachment_path
                ? [
                    'url' => url(Storage::url($this->attachment_path)),
                    'original_name' => $this->attachment_original_name,
                    'type' => $this->attachment_type,
                ]
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

