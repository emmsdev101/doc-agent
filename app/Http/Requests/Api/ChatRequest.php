<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kb_id' => ['required', 'uuid'],
            'message' => ['required', 'string', 'min:1', 'max:4000'],
            'session_id' => ['nullable', 'uuid'],
            'stream' => ['sometimes', 'boolean'],
        ];
    }
}
