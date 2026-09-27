<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateBookApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        $book = $this->route('book');

        return $this->user() !== null && ($book === null || $this->user()->can('update', $book));
    }

    public function rules(): array
    {
        $book = $this->route('book');

        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => [
                'nullable',
                'string',
                'size:13',
                Rule::unique('books', 'isbn')->ignore($book?->id),
            ],
            'published_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['integer', 'exists:genres,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルは必須です。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'author.required' => '著者名は必須です。',
            'author.max' => '著者名は255文字以内で入力してください。',
            'isbn.size' => 'ISBNは13桁で入力してください。',
            'isbn.unique' => 'このISBNは既に他の書籍で登録されています。',
            'published_date.date' => '正しい日付形式で入力してください。',
            'image_url.url' => '有効なURL形式で入力してください。',
            'image_url.max' => '画像URLは2048バイト以内で入力してください。',
            'genres.required' => 'ジャンルは1つ以上選択してください。',
            'genres.min' => 'ジャンルは1つ以上選択してください。',
            'genres.*.exists' => '選択されたジャンルが存在しません。',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => '入力内容に不備があります。',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
