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
            // --- 山田太郎 (主要シナリオ検証用 5件) ---
            [
                'user_id' => $yamada->id,
                'book_id' => $books[0]->id,
                'target_date' => $today->copy()->addDays(3), // 3日前リマインダー対象
                'completed_at' => null,
                'status' => 'in_progress',
            ],
            [
                'user_id' => $yamada->id,
                'book_id' => $books[1]->id,
                'target_date' => $today->copy(), // 当日リマインダー対象
                'completed_at' => null,
                'status' => 'in_progress',
            ],
            [
                'user_id' => $yamada->id,
                'book_id' => $books[2]->id,
                'target_date' => $today->copy()->subDays(3), // 自動失効 & 3日後再エンゲージメント対象
                'completed_at' => null,
                'status' => 'in_progress',
            ],
            [
                'user_id' => $yamada->id,
                'book_id' => $books[3]->id,
                'target_date' => $today->copy()->addDays(7), // リマインダー対象外
                'completed_at' => null,
                'status' => 'in_progress',
            ],
            [
                'user_id' => $yamada->id,
                'book_id' => $books[4]->id,
                'target_date' => $today->copy()->subDays(10),
                'completed_at' => $today->copy()->subDays(5), // 完了済み
                'status' => 'completed',
            ],

            // --- 鈴木花子 (認可テスト用 1件) ---
            [
                'user_id' => $suzuki->id,
                'book_id' => $books[5]->id,
                'target_date' => $today->copy()->addDays(5), // 403 エラーテスト用
                'completed_at' => null,
                'status' => 'in_progress',
            ],
        ];

        foreach ($plans as $planData) {
            ReadingPlan::create($planData);
        }
    }
}
