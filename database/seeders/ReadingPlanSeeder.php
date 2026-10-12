<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReadingPlanSeeder extends Seeder
{
    public function run(): void
    {
        $yamada = User::where('email', 'yamada@example.com')->first();
        $suzuki = User::where('email', 'suzuki@example.com')->first();
        $books = Book::all();

        if (! $yamada || ! $suzuki || $books->count() < 6) {
            return;
        }

        $today = Carbon::today();

        $plans = [
            [
                'user_id' => $yamada->id,
                'book_id' => $books[0]->id,
                'target_date' => $today->copy()->addDays(3),
                'completed_at' => null,
                'status' => 'in_progress',
            ],
            [
                'user_id' => $yamada->id,
                'book_id' => $books[1]->id,
                'target_date' => $today->copy(),
                'completed_at' => null,
                'status' => 'in_progress',
            ],
            [
                'user_id' => $yamada->id,
                'book_id' => $books[2]->id,
                'target_date' => $today->copy()->subDays(3),
                'completed_at' => null,
                'status' => 'in_progress',
            ],
            [
                'user_id' => $yamada->id,
                'book_id' => $books[3]->id,
                'target_date' => $today->copy()->addDays(7),
                'completed_at' => null,
                'status' => 'in_progress',
            ],
            [
                'user_id' => $yamada->id,
                'book_id' => $books[4]->id,
                'target_date' => $today->copy()->subDays(10),
                'completed_at' => $today->copy()->subDays(5),
                'status' => 'completed',
            ],

            [
                'user_id' => $suzuki->id,
                'book_id' => $books[5]->id,
                'target_date' => $today->copy()->addDays(5),
                'completed_at' => null,
                'status' => 'in_progress',
            ],
        ];

        foreach ($plans as $planData) {
            ReadingPlan::create($planData);
        }
    }
}
