<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isAdminOrModerator();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_number' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            
            // Translations
            'translations' => ['sometimes', 'array', 'min:1'],
            'translations.uz' => ['sometimes', 'array'],
            'translations.uz.title' => ['required_with:translations.uz', 'string', 'max:500'],
            'translations.uz.description' => ['nullable', 'string', 'max:2000'],
            
            'translations.ru' => ['sometimes', 'array'],
            'translations.ru.title' => ['required_with:translations.ru', 'string', 'max:500'],
            'translations.ru.description' => ['nullable', 'string', 'max:2000'],
            
            'translations.en' => ['sometimes', 'array'],
            'translations.en.title' => ['required_with:translations.en', 'string', 'max:500'],
            'translations.en.description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}



