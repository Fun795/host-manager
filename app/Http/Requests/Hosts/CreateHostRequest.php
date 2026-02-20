<?php

namespace App\Http\Requests\Hosts;

use Illuminate\Foundation\Http\FormRequest;

class CreateHostRequest extends FormRequest
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
            'hostname' => [
                'required',
                'string',
                'max:36',
                'regex:/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/',
                //латиница, цифры, дефисы, отсутствие дефисов в начале и конце
            ],
            'ip' => [
                'required',
                'ip',
            ],
            'tags' => [
                'nullable',
                'array',
            ],
            'tags.*' => [
                'string',
                'max:32',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'hostname.regex' =>
                'Hostname может содержать только строчные латинские буквы, цифры и дефисы. ' .
                'Не должен начинаться или заканчиваться на дефис.',
            'tags.*.string' => 'Каждый элемент внутри tags должен быть строкой'
        ];
    }
}
