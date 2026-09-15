<?php

namespace App\Http\Requests\KnowledgeBases;

use Illuminate\Foundation\Http\FormRequest;

class UpdateKnowledgeBaseRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'welcome_message' => ['nullable', 'string', 'max:255'],
            'system_prompt' => ['nullable', 'string', 'max:5000'],
            'allowed_origins' => ['nullable', 'string', 'max:2000'],
            'primary_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'is_active' => ['sometimes', 'boolean'],
            'rotate_token' => ['sometimes', 'boolean'],
        ];
    }
}
