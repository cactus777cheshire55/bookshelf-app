<?php

namespace App\Http\Controllers;

use App\Models\ReadingPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $completedBooksCount = $user->readingPlans()
            ->where('status', ReadingPlan::STATUS_COMPLETED)
            ->count();

        $reviewsCount = $user->reviews()->count();
        $averageRating = round($user->reviews()->avg('rating') ?? 0, 1);

        $ratingDistribution = [
            5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0,
        ];

        $rawDistribution = $user->reviews()
            ->select('rating', DB::raw('count(*) as count'))
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        foreach ($rawDistribution as $rating => $count) {
            $ratingDistribution[$rating] = $count;
        }

        $topRatedBooks = $user->reviews()
            ->with('book')
            ->orderByDesc('rating')
            ->take(5)
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->book->id ?? null,
                    'title' => $review->book->title ?? '',
                    'author' => $review->book->author ?? '',
                    'rating' => $review->rating,
                ];
            });

        $preferredGenres = DB::table('reviews')
            ->join('book_genre', 'reviews.book_id', '=', 'book_genre.book_id')
            ->join('genres', 'book_genre.genre_id', '=', 'genres.id')
            ->where('reviews.user_id', $user->id)
            ->select(
                'genres.id',
                'genres.name',
                DB::raw('count(*) as count'),
                DB::raw('avg(reviews.rating) as average_rating')
            )
            ->groupBy('genres.id', 'genres.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function ($genre) {
                return [
                    'id' => $genre->id,
                    'name' => $genre->name,
                    'count' => $genre->count,
                    'average_rating' => $genre->average_rating,
                ];
            });

        $stats = [
            'summary' => [
                'total_reviews' => $reviewsCount,
                'books_read' => $completedBooksCount,
                'average_rating' => $averageRating,
            ],
            'rating_distribution' => collect($ratingDistribution),
            'top_rated_books' => $topRatedBooks,
            'genre_ratings' => $preferredGenres,
        ];

        $recentCompletedPlans = $user->readingPlans()
            ->with('book')
            ->where('status', ReadingPlan::STATUS_COMPLETED)
            ->orderByDesc('completed_at')
            ->take(5)
            ->get();

        return view('reports.index', compact(
            'stats',
            'recentCompletedPlans'
        ));
    }
}
