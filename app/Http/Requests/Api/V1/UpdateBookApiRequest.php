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
            'image_url' => ['nullable', 'url', 'max:255'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['integer', 'exists:genres,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルを入力してください',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'author.required' => '著者名を入力してください',
            'author.max' => '著者名は255文字以内で入力してください。',
            'isbn.required' => 'ISBN-13を入力してください',
            'isbn.size' => 'ISBN-13の値が不正です',
            'isbn.unique' => 'このISBNは既に他の書籍で登録されています',
            'published_date.date' => '年月日の形式に従ってください',
            'image_url.url' => '有効なURL形式で入力してください',
            'image_url.max' => '画像の文字数は255文字以内で入力してください',
            'genres.required' => 'ジャンル選んでください',
            'genres.min' => 'ジャンル選んでください',
            'genres.*.exists' => '選択されたジャンルが存在しません',
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
