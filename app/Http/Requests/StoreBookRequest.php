<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'string', 'size:13', 'regex:/^[0-9]+$/', 'unique:books,isbn'],
            'published_date' => ['required', 'date'],
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
            'title.max' => 'タイトルは255文字以内で入力してください',
            'author.required' => '著者を入力してください',
            'author.max' => '著者は255文字以内で入力してください',
            'isbn.required' => 'ISBN-13を入力してください',
            'isbn.size' => 'ISBN-13の値が不正です',
            'isbn.regex' => 'ISBN-13の値が不正です',
            'isbn.unique' => 'このISBNは既に他の書籍で登録されています',
            'published_date.required' => '日付を入力してください',
            'published_date.date' => '年月日の形式に従ってください',
            'image_url.url' => '有効なURL形式で入力してください',
            'image_url.max' => '画像の文字数は255文字以内で入力してください',
            'genres.required' => 'ジャンルを選んでください',
            'genres.min' => 'ジャンルを選んでください',
        ];
    }
}
