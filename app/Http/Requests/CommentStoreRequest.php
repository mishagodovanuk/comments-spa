<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CommentStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
            'user_name' => ['required', 'string', 'max:70', 'regex:/^[a-zA-Z0-9]+$/'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'home_page' => ['nullable', 'string', 'url', 'max:255'],

            'captcha_token' => ['required', 'string', 'max:64'],
            'captcha_answer' => ['required', 'string', 'max:20'],

            'text' => ['required', 'string', 'max:5000'],
            'file' => ['nullable', 'file'],
        ];
    }
}
