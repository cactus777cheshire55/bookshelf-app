<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreBookApiRequest;
use App\Http\Requests\Api\V1\UpdateBookApiRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookApiController extends Controller
{
        public function index(Request $request): AnonymousResourceCollection
    {
        $query = Book::with(['genres'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews'); 

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
            'title'  => $query->orderBy('title', 'asc'),
            'rating' => $query->orderByRaw('reviews_avg_rating IS NULL ASC')->orderBy('reviews_avg_rating', 'desc'),
            default  => $query->latest(),
        };

        $perPage = (int) $request->input('per_page', 20);
        $books = $query->paginate($perPage);

        return BookResource::collection($books);
    }

    public function show(Book $book): BookResource
    {
        $book->load([
            'genres',
            'reviews.user',
        ])->loadAvg('reviews', 'rating')->loadCount('reviews');

        return new BookResource($book);
    }

    public function store(StoreBookApiRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $book = $request->user()->books()->create($validated);

        if (isset($validated['genres'])) {
            $book->genres()->sync($validated['genres']);
        }

        $book->load('genres');

        return (new BookResource($book))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateBookApiRequest $request, Book $book): BookResource
    {
        $this->authorize('update', $book);

        $validated = $request->validated();

        $book->update($validated);

        if (isset($validated['genres'])) {
            $book->genres()->sync($validated['genres']);
        }

        $book->load('genres');

        return new BookResource($book);
    }

    public function destroy(Book $book): JsonResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return response()->json([
            'message' => '書籍を削除しました。',
        ], 200);
    }
}
