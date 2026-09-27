<?php 

namespace App\Http\Requests;

use App\Models\Book;
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
            'title' => ['required', 'string', 'max:60'],
            'author' => ['required', 'string', 'max:60'],
            'isbn' => [
                'required',
                'string',
                'size:13',
                'regex:/^[0-9]+$/',

                Rule::unique('books', 'isbn')->ignore($book->id),
            ],
            'published_date' => ['nullable', 'date'],
            'description'    => ['nullable', 'string', 'max:400'],
            'image'      => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'genres'         => ['required', 'array', 'min:1'],
            'genres.*'       => ['integer', 'exists:genres,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'  => 'タイトルを入力してください',
            'title.max' => 'タイトルは60文字で入力してください',
            'author.required' => '著者を入力してください',
            'author.max' => '著者は60文字以内で入力してください',
            'isbn' => 'ISBN-13の値が不正です',
            'isbn.unique' => 'このISBNは既に他の書籍で登録されています',
            'image.max' => '画像のファイルサイズは2048バイト以内で入力してください',
            'genres' => 'ジャンルを選んでください',
        ];
    }
}