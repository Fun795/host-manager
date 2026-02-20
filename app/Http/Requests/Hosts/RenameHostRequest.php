<?php

namespace App\Http\Requests\Hosts;

use Illuminate\Foundation\Http\FormRequest;

class RenameHostRequest extends FormRequest
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
            'new_hostname' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/',
                //латиница, цифры, дефисы, отсутствие дефисов в начале и конце
            ],
            'header_idempotency_key' => [
                'required',
                'uuid'
            ]
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'header_idempotency_key' => $this->header('Idempotency-Key'),
        ]);
    }

    public function messages(): array
    {
        return [
            'new_hostname.regex' =>
                'Hostname может содержать только строчные латинские буквы, цифры и дефисы. ' .
                'Не должен начинаться или заканчиваться на дефис.',
            'header_idempotency_key.required' => 'Необходимо передать заголовок запроса \'Idempotency-Key\'',
            'header_idempotency_key.uuid' => 'Заголовок запроса \'Idempotency-Key\' должен быть корректным uuid'
        ];
    }
}
