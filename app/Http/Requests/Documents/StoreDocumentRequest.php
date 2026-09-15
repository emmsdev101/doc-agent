<?php

namespace App\Http\Requests\Documents;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
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
        $mimes = implode(',', config('docagent.uploads.mimes'));
        $max = (int) config('docagent.uploads.max_kilobytes');

        return [
            'file' => ['required', 'file', "mimes:{$mimes}", "max:{$max}"],
        ];
    }
}
