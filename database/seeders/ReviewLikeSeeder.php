<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $reviews = Review::all();

        if ($users->isEmpty() || $reviews->isEmpty()) {
            return;
        }

        foreach ($reviews as $review) {
            // 自分のレビュー以外から0〜3人のユーザーを選出
            $eligibleUsers = $users->where('id', '!=', $review->user_id);
            $likeCount = min(rand(0, 3), $eligibleUsers->count());

            if ($likeCount > 0) {
                $likedUsers = $eligibleUsers->random($likeCount);
                $review->likedByUsers()->syncWithoutDetaching($likedUsers->pluck('id'));
            }
        }
    }
}
