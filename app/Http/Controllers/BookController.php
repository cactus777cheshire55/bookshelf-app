<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(Request $request): View
    {
        $query = Book::with(['genres'])->withAvg('reviews', 'rating');

        if ($keyword = $request->input('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%");
            });
        }

        if ($genreId = $request->input('genre')) {
            $query->whereHas('genres', function ($q) use ($genreId) {
                $q->where('genres.id', $genreId);
            });
        }

        match ($request->input('sort')) {
            'oldest' => $query->oldest(),
            'title' => $query->orderBy('title', 'asc'),
            'rating' => $query->orderByRaw('reviews_avg_rating IS NULL ASC, reviews_avg_rating DESC')->orderBy('reviews_avg_rating', 'desc'), default => $query->latest(),
        };

        $books = $query->paginate(10)->withQueryString();
        $genres = Genre::all();

        return view('books.index', compact('books', 'genres'));
    }

    public function show(Book $book): View
    {
        $book->load([
            'genres',
            'reviews.user',
            'reviews.likedByUsers',
        ]);

        return view('books.show', compact('book'));
    }

    public function create(): View
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    public function store(StoreBookRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $book = $request->user()->books()->create(collect($validated)->except(['image', 'image_url'])->toArray());
        $book->genres()->sync($request->input('genres', []));

        if ($request->filled('image_url')) {
            $book->image_url = $request->input('image_url');
        } else {
            $book->image_url = sprintf('https://placehold.co/200x300?text=Book+%d', $book->id);
        }
        $book->save();

        return redirect()->route('books.show', $book)
            ->with('success', '書籍を登録しました');
    }

    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        $genres = Genre::all();
        $book->load('genres');

        return view('books.edit', compact('book', 'genres'));
    }

    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        $validated = $request->validated();

        $book->fill(collect($validated)->except(['image', 'image_url'])->toArray());

        if ($request->filled('image_url')) {
            $book->image_url = $request->input('image_url');
        }

        $book->save();

        $book->genres()->sync($request->input('genres', []));

        return redirect()->route('books.show', $book)
            ->with('success', '書籍情報を更新しました。');
    }

    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);
        $book->delete();

        return redirect()->route('books.index')
            ->with('success', '書籍を削除しました');
    }

    public function notificationIndex(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->get();

        return view('notifications.index', compact('notifications'));
    }

    public function readNotification(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return back()->with('success', '通知を既読にしました。');
    }

    public function rankingIndex(): View
    {
        $rankedBooks = Book::whereHas('reviews')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderBy('reviews_avg_rating', 'desc')
            ->limit(10)
            ->get();

        return view('ranking.index', compact('rankedBooks'));
    }

    public function fetchIsbn(string $isbn): JsonResponse
    {
        if (strlen($isbn) !== 13 || ! ctype_digit($isbn)) {
            return response()->json([
                'error' => 'ISBNは13桁の半角数字で入力してください。',
            ], 400);
        }

        try {
            $openBdResponse = Http::get('https://api.openbd.jp/v1/get', [
                'isbn' => $isbn,
            ]);

            if ($openBdResponse->successful()) {
                $openBdData = $openBdResponse->json();

                if (! empty($openBdData) && $openBdData[0] !== null) {
                    $summary = $openBdData[0]['summary'];

                    $pubdate = $summary['pubdate'] ?? '';
                    if (strlen($pubdate) === 8) {
                        $pubdate = substr($pubdate, 0, 4).'-'.substr($pubdate, 4, 2).'-'.substr($pubdate, 6, 2);
                    } elseif (strlen($pubdate) === 6) {
                        $pubdate = substr($pubdate, 0, 4).'-'.substr($pubdate, 4, 2).'-01';
                    }

                    $imageUrl = $summary['cover'] ?? '';
                    if (str_starts_with($imageUrl, 'http://')) {
                        $imageUrl = str_replace('http://', 'https://', $imageUrl);
                    }

                    return response()->json([
                        'title' => $summary['title'] ?? '',
                        'author' => $summary['author'] ?? '',
                        'description' => '',
                        'published_date' => $pubdate,
                        'image_url' => $imageUrl,
                    ]);
                }
            }
            $googleResponse = Http::get('https://www.googleapis.com/books/v1/volumes', [
                'q' => "isbn:{$isbn}",
            ]);

            $googleData = $googleResponse->json();
            if (empty($googleData['totalItems']) || empty($googleData['items'][0]['volumeInfo'])) {
                return response()->json([
                    'error' => '該当する書籍が見つかりませんでした。',
                ], 404);
            }

            $volumeInfo = $googleData['items'][0]['volumeInfo'];

            $authors = isset($volumeInfo['authors'])
                ? implode(', ', $volumeInfo['authors'])
                : '';

            $imageUrl = $volumeInfo['imageLinks']['thumbnail']
                ?? $volumeInfo['imageLinks']['smallThumbnail']
                ?? '';

            if (str_starts_with($imageUrl, 'http://')) {
                $imageUrl = str_replace('http://', 'https://', $imageUrl);
            }

            return response()->json([
                'title' => $volumeInfo['title'] ?? '',
                'author' => $authors,
                'description' => $volumeInfo['description'] ?? '',
                'published_date' => $volumeInfo['publishedDate'] ?? '',
                'image_url' => $imageUrl,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => '通信エラーが発生しました。時間をおいて再度お試しください。',
            ], 500);
        }
    }
}
