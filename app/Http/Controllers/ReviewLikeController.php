<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewLikeController extends Controller
{
    public function toggle(Request $request, Review $review): RedirectResponse
    {
        $user = $request->user();

        if ($review->user_id === $user->id) {
            return back()->with('error', '自分のレビューに「いいね」することはできません。');
        }

        $changes = $user->likedReviews()->toggle($review->id);

        $statusMessage = count($changes['attached']) > 0
            ? 'レビューにいいねしました。'
            : 'いいねを取り消しました。';

        return back()->with('success', $statusMessage);
    }
}