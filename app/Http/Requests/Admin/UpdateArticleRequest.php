<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        
        // Only admin can update articles
        return $user->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $articleId = $this->route('article') ?? $this->route('id');

        return [
            'chapter_id' => ['sometimes', 'integer', 'exists:chapters,id'],
            'article_number' => [
                'sometimes',
                'string',
                'max:20',
                'regex:/^[0-9]+(-[0-9]+)?$/',
                Rule::unique('articles', 'article_number')->ignore($articleId)->whereNull('deleted_at'),
            ],
            'order_number' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            
            // Translations
            'translations' => ['sometimes', 'array', 'min:1'],
            'translations.uz' => ['sometimes', 'array'],
            'translations.uz.title' => ['required_with:translations.uz', 'string', 'max:500'],
            'translations.uz.content' => ['required_with:translations.uz', 'string'],
            'translations.uz.summary' => ['nullable', 'string'],
            'translations.uz.keywords' => ['nullable', 'array'],
            'translations.uz.keywords.*' => ['string'],
            
            'translations.ru' => ['sometimes', 'array'],
            'translations.ru.title' => ['required_with:translations.ru', 'string', 'max:500'],
            'translations.ru.content' => ['required_with:translations.ru', 'string'],
            'translations.ru.summary' => ['nullable', 'string'],
            'translations.ru.keywords' => ['nullable', 'array'],
            'translations.ru.keywords.*' => ['string'],

            // Comment (optional)
            'comment' => ['sometimes', 'array'],
            'comment.uz' => ['nullable', 'string'],
            'comment.ru' => ['nullable', 'string'],
        ];
    }
}
