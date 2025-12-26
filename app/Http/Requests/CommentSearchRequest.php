<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CommentSearchRequest.
 */
final class CommentSearchRequest extends FormRequest
{
    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * Return search query.
     *
     * @return string
     */
    public function q(): string
    {
        return (string) $this->validated('q');
    }

    /**
     * Return page.
     *
     * @return int
     */
    public function page(): int
    {
        return (int) ($this->validated('page') ?? 1);
    }

    /**
     * Return per page.
     *
     * @return int
     */
    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 20);
    }
}
