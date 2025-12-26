<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CommentSearchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function q(): string
    {
        return (string) $this->validated('q');
    }

    public function page(): int
    {
        return (int) ($this->validated('page') ?? 1);
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 20);
    }
}
