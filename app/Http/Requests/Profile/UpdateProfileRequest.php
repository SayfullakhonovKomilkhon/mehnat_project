<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'phone' => [
                'nullable',
                'string',
                'regex:/^\+?[0-9]{9,15}$/',
                Rule::unique('users', 'phone')->ignore($this->user()->id),
            ],
            'preferred_locale' => ['sometimes', 'string', 'in:uz,ru,en'],
        ];
    }
}



