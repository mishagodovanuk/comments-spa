<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CommentIndexRequest.
 */
final class CommentIndexRequest extends FormRequest
{
    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'page' => ['integer', 'min:1'],
            'sort' => ['in:user_name,email,created_at'],
            'direction' => ['in:asc,desc'],
        ];
    }

    /**
     * Return current page.
     *
     * @return int
     */
    public function page(): int
    {
        return (int) $this->input('page', 1);
    }

    /**
     * Return sorting.
     *
     * @return string
     */
    public function sort(): string
    {
        return $this->input('sort', 'created_at');
    }

    /**
     * Return direction.
     *
     * @return string
     */
    public function direction(): string
    {
        return $this->input('direction', 'desc');
    }
}
