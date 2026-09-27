<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest; 
use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function store(ReviewRequest $request, Book $book): RedirectResponse
    {
        $user = $request->user();

        $alreadyReviewed = $user->reviews()->where('book_id', $book->id)->exists();
        if ($alreadyReviewed) {
            return back()
                ->withInput()
                ->withErrors(['comment' => 'この書籍には既にレビューを投稿しています。']);
        }

        $validated = $request->validated();

        $book->reviews()->create([
            'user_id' => $user->id,
            'rating'  => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return redirect()->route('books.show', $book)
            ->with('success', 'レビューを投稿しました。');
    }

    public function edit(Review $review): View
    {
        $this->authorize('update', $review);

        $review->load('book');

        return view('reviews.edit', compact('review'));
    }

    public function update(ReviewRequest $request, Review $review): RedirectResponse
    {
        $this->authorize('update', $review);

        $validated = $request->validated();

        $review->update($validated);

        return redirect()->route('books.show', $review->book_id)
            ->with('success', 'レビューを更新しました。');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $bookId = $review->book_id;
        $review->delete();

        return redirect()->route('books.show', $bookId)
            ->with('success', 'レビューを削除しました。');
    }
}
