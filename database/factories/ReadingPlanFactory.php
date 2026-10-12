<?php

namespace Database\Factories;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReadingPlanFactory extends Factory
{
    protected $model = ReadingPlan::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'target_date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'status' => ReadingPlanStatus::InProgress,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReadingPlanStatus::Completed,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'target_date' => fake()->dateTimeBetween('-1 month', '-1 day')->format('Y-m-d'),
            'status' => ReadingPlanStatus::Expired,
        ]);
    }
}
