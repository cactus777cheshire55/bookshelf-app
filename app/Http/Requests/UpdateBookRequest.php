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
            'published_date' => ['nullable', 'date','after_or_equal:1900-01-01','before_or_equal:+5 years',],
            'description' => ['nullable', 'string', 'max:2000'],
            'image_url' => ['nullable', 'url', 'max:512'],
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
            'isbn.unique' => 'このISBNは既に他の書籍で登録されています',
            'image_url.max' => 'URLの長さは512文字までにしてください',
            'description.max' => '説明は2000文字以内で入力してください',
            'genres' => 'ジャンルを選んでください',
            'published_date.after_or_equal' => '公開日は西暦1900年以降の日付を指定してください',
            'published_date.before_or_equal' => '公開日は5年以内の日付を指定してください',
        ];
    }
}
