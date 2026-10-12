<?php

namespace Database\Factories;

use App\Models\Genre;
use Illuminate\Database\Eloquent\Factories\Factory;

class GenreFactory extends Factory
{
    protected $model = Genre::class;

    public function definition(): array
    {
        return [
                'name' => fake()->unique()->randomElement([
                '小説', 'ビジネス', '技術書', '自己啓発', 'エッセイ', 
                '歴史', '科学', '芸術', '料理', '旅行',
            ]),
        ];
    }
}
