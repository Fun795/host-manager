<?php

namespace App\Http\Requests\Hosts;

use Illuminate\Foundation\Http\FormRequest;

class ListHostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string'],
            'page.*' => ['nullable', 'array'],
            'page.size' => ['nullable', 'integer', 'max:100'],
            'page.after' => ['nullable', 'string'],
        ];
    }
}
