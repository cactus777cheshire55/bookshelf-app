<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        $book = $this->route('book');

        return $this->user() !== null && $this->user()->can('update', $book);
    }

    public function rules(): array
    {
        $book = $this->route('book');

        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => [
                'required',
                'string',
                'size:13',
                'regex:/^[0-9]+$/',

                Rule::unique('books', 'isbn')->ignore($book->id),
            ],
            'published_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'url', 'max:255'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['integer', 'exists:genres,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルを入力してください',
            'title.max' => 'タイトルは255文字で入力してください',
            'author.required' => '著者を入力してください',
            'author.max' => '著者は255文字以内で入力してください',
            'isbn' => 'ISBN-13の値が不正です',
            'isbn.required' => 'ISBN-13を入力してください',
            'isbn.unique' => 'このISBNは既に他の書籍で登録されています',
            'image_url.max' => 'URLの長さは255文字までにしてください',
            'description.max' => '説明は255文字以内で入力してください',
            'genres' => 'ジャンルを選んでください',
            'published_date' => '年月日の形式に従ってください',
        ];
    }
}
