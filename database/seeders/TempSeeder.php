<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TempSeeder extends Seeder
{
    private const TARGET_BOOKS = 45;

    private const TARGET_BOOK_GENRES = 60;

    private const TARGET_REVIEWS = 30;

    private const TARGET_FAVORITES = 20;

    private const TARGET_REVIEW_LIKES = 10;

    private const TARGET_READING_PLANS = 20;

    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            GenreSeeder::class,
        ]);

        if (Book::query()->doesntExist()) {
            $this->call(BookSeeder::class);
        }

        $this->ensureBooks();
        $this->ensureBookGenres();
        $this->ensureReviews();
        $this->ensureFavorites();
        $this->ensureReviewLikes();
        $this->ensureReadingPlans();
    }

    private function ensureBooks(): void
    {
        $userIds = User::query()->orderBy('id')->pluck('id')->all();
        $authors = ['青木 遥', '森川 直', '高橋 梓', '佐々木 蓮', '中村 紬'];
        $number = 1;

        while (Book::query()->count() < self::TARGET_BOOKS) {
            $isbn = $this->isbnFor($number);
            $number++;

            if (Book::query()->where('isbn', $isbn)->exists()) {
                continue;
            }

            $bookNumber = $number - 1;
            Book::query()->create([
                'user_id' => $userIds[($bookNumber - 1) % count($userIds)],
                'title' => sprintf('読書案内 %03d', $bookNumber),
                'author' => $authors[($bookNumber - 1) % count($authors)],
                'isbn' => $isbn,
                'published_date' => sprintf(
                    '%04d-%02d-%02d',
                    2000 + ($bookNumber % 25),
                    (($bookNumber - 1) % 12) + 1,
                    (($bookNumber - 1) % 28) + 1
                ),
                'description' => sprintf('ダミー書籍%dの紹介文です。', $bookNumber),
                'image_url' => sprintf('https://placehold.co/200x300?text=Book+%03d', $bookNumber),
            ]);
        }
    }

    private function ensureBookGenres(): void
    {
        $genreCount = DB::table('book_genre')->count();
        if ($genreCount >= self::TARGET_BOOK_GENRES) {
            return;
        }

        $genres = Genre::query()->orderBy('id')->get();
        $books = Book::query()->with('genres')->orderBy('id')->get();

        foreach ($books as $book) {
            $attachedGenreIds = $book->genres->modelKeys();

            foreach ($genres as $genre) {
                if (in_array($genre->id, $attachedGenreIds, true)) {
                    continue;
                }

                $book->genres()->attach($genre->id);
                $genreCount++;

                if ($genreCount >= self::TARGET_BOOK_GENRES) {
                    return;
                }
            }
        }
    }

    private function ensureReviews(): void
    {
        $users = User::query()->orderBy('id')->get();
        $books = Book::query()->orderBy('id')->get();
        $comments = [
            '読みやすく、最後まで楽しめました。',
            '印象に残る内容でした。',
            'もう一度読み返したい一冊です。',
            '新しい視点を得られました。',
            '気軽に読めて面白かったです。',
        ];

        foreach ($books as $bookIndex => $book) {
            foreach ($users as $userIndex => $user) {
                if (Review::query()->count() >= self::TARGET_REVIEWS) {
                    return;
                }

                if (Review::query()
                    ->where('user_id', $user->id)
                    ->where('book_id', $book->id)
                    ->exists()) {
                    continue;
                }

                Review::query()->create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'rating' => (($bookIndex + $userIndex) % 5) + 1,
                    'comment' => $comments[($bookIndex + $userIndex) % count($comments)],
                ]);
            }
        }
    }

    private function ensureFavorites(): void
    {
        $favoriteCount = DB::table('favorites')->count();
        if ($favoriteCount >= self::TARGET_FAVORITES) {
            return;
        }

        $users = User::query()->orderBy('id')->get();
        $books = Book::query()->orderBy('id')->get();

        foreach ($users as $user) {
            foreach ($books as $book) {
                if (DB::table('favorites')
                    ->where('user_id', $user->id)
                    ->where('book_id', $book->id)
                    ->exists()) {
                    continue;
                }

                $user->favoriteBooks()->attach($book->id);
                $favoriteCount++;

                if ($favoriteCount >= self::TARGET_FAVORITES) {
                    return;
                }
            }
        }
    }

    private function ensureReviewLikes(): void
    {
        $likeCount = DB::table('review_likes')->count();
        if ($likeCount >= self::TARGET_REVIEW_LIKES) {
            return;
        }

        $users = User::query()->orderBy('id')->get();
        $reviews = Review::query()->orderBy('id')->get();

        foreach ($users as $user) {
            foreach ($reviews as $review) {
                if ($review->user_id === $user->id) {
                    continue;
                }

                if (DB::table('review_likes')
                    ->where('user_id', $user->id)
                    ->where('review_id', $review->id)
                    ->exists()) {
                    continue;
                }

                $user->likedReviews()->attach($review->id);
                $likeCount++;

                if ($likeCount >= self::TARGET_REVIEW_LIKES) {
                    return;
                }
            }
        }
    }

    private function ensureReadingPlans(): void
    {
        $planCount = DB::table('reading_plans')->count();
        if ($planCount >= self::TARGET_READING_PLANS) {
            return;
        }

        $users = User::query()->orderBy('id')->get();
        $books = Book::query()->orderBy('id')->get();

        foreach ($users as $user) {
            foreach ($books as $book) {
                if (DB::table('reading_plans')
                    ->where('user_id', $user->id)
                    ->where('book_id', $book->id)
                    ->exists()) {
                    continue;
                }

                $now = now();
                DB::table('reading_plans')->insert([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'target_date' => $now->copy()->addDays(($planCount * 7) % 90)->toDateString(),
                    'completed_at' => null,
                    'status' => 'in_progress',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $planCount++;

                if ($planCount >= self::TARGET_READING_PLANS) {
                    return;
                }
            }
        }
    }

    private function isbnFor(int $number): string
    {
        $isbnBody = '979000000'.str_pad((string) $number, 3, '0', STR_PAD_LEFT);
        $sum = 0;

        for ($index = 0; $index < strlen($isbnBody); $index++) {
            $sum += (int) $isbnBody[$index] * ($index % 2 === 0 ? 1 : 3);
        }

        return $isbnBody.((10 - ($sum % 10)) % 10);
    }
}
