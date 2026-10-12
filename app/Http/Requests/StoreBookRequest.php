<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'string', 'size:13',  'regex:/^[0-9]+$/', 'unique:books,isbn'],
            'published_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'url', 'max:255'],
            'genres' => ['required', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルを入力してください',
            'title.max' => 'タイトルは255文字以内で入力してください',
            'author.required' => '著者を入力してください',
            'author.max' => '著者は255文字で入力してください',
            'isbn' => 'ISBN-13の値が不正です',
            'isbn.unique' => 'このISBNは既に他の書籍で登録されています',
            'isbn.required' => 'ISBN-13を入力してください',
            'description.max' => '説明は255文字以内で入力してください',
            'genres' => 'ジャンルを選んでください',
            'image.max' => 'URLの文字数は255文字以内で入力してください',
            'published_date' => '年月日の形式に従ってください',
        ];
    }
}
