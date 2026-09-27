<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        $genreId = $this->route('genre')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:30',

                $genreId 
                    ? Rule::unique('genres', 'name')->ignore($genreId)
                    : 'unique:genres,name',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'ジャンル名を入力してください',
            'name.max'      => 'ジャンル名は30文字以内で入力してください',
            'name.unique'   => 'このジャンル名は既に登録されています',
        ];
    }
}
