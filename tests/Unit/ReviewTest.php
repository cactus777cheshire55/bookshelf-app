<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic unit test example.
     */

    /** @test */
    public function コメント付きレビューが投稿できるか(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating'  => 5,
            'comment' => 'テストです',
        ]);

        $this->assertEquals(5, $review->rating);
        $this->assertEquals('テストです', $review->comment);
    }

        /** @test */
        public function コメントなしレビューが投稿できるか(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating'  => 1,
            'comment' => '',
        ]);

        $this->assertEquals(1, $review->rating);
        $this->assertEquals('', $review->comment);
    }
}
