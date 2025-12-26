<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CommentIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['integer', 'min:1'],
            'sort' => ['in:user_name,email,created_at'],
            'direction' => ['in:asc,desc'],
        ];
    }

    public function page(): int
    {
        return (int) $this->input('page', 1);
    }

    public function sort(): string
    {
        return $this->input('sort', 'created_at');
    }

    public function direction(): string
    {
        return $this->input('direction', 'desc');
    }
}
