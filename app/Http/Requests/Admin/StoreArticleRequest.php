<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow admin to create articles
        return $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'chapter_id' => ['required', 'integer', 'exists:chapters,id'],
            'article_number' => ['required', 'string', 'max:20', 'unique:articles,article_number', 'regex:/^[0-9]+(-[0-9]+)?$/'],
            'order_number' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            
            // Translations (at least Uzbek required)
            'translations' => ['required', 'array', 'min:1'],
            'translations.uz' => ['required', 'array'],
            'translations.uz.title' => ['required', 'string', 'max:500'],
            'translations.uz.content' => ['required', 'string'],
            'translations.uz.summary' => ['nullable', 'string', 'max:1000'],
            'translations.uz.keywords' => ['nullable', 'array'],
            'translations.uz.keywords.*' => ['string', 'max:100'],
            
            'translations.ru' => ['sometimes', 'array'],
            'translations.ru.title' => ['required_with:translations.ru', 'string', 'max:500'],
            'translations.ru.content' => ['required_with:translations.ru', 'string'],
            'translations.ru.summary' => ['nullable', 'string', 'max:1000'],
            'translations.ru.keywords' => ['nullable', 'array'],
            'translations.ru.keywords.*' => ['string', 'max:100'],
            
            'translations.en' => ['sometimes', 'array'],
            'translations.en.title' => ['required_with:translations.en', 'string', 'max:500'],
            'translations.en.content' => ['required_with:translations.en', 'string'],
            'translations.en.summary' => ['nullable', 'string', 'max:1000'],
            'translations.en.keywords' => ['nullable', 'array'],
            'translations.en.keywords.*' => ['string', 'max:100'],

            // Comment (optional)
            'comment' => ['nullable', 'array'],
            'comment.uz' => ['nullable', 'string'],
            'comment.ru' => ['nullable', 'string'],
            'comment.en' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'article_number.regex' => __('validation.regex', ['attribute' => 'номер статьи']),
            'article_number.unique' => __('validation.unique', ['attribute' => 'номер статьи']),
            'translations.uz.title.required' => __('validation.required', ['attribute' => 'узбекское название']),
            'translations.uz.content.required' => __('validation.required', ['attribute' => 'узбекский текст']),
        ];
    }
}



