<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 書籍にレビュー投稿できるか(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post("/books/{$book->id}/reviews", [
            'rating'  => 3,
            'comment' => 'テストコメントです',
        ]);

        $response->assertRedirect("/books/{$book->id}");
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating'  => 3,
        ]);
    }

    /** @test */
    public function 同一書籍に2回連続でレビュー投稿できないか(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        Review::factory()->create(['user_id' => $user->id, 'book_id' => $book->id]);

        $response = $this->actingAs($user)->post("/books/{$book->id}/reviews", [
            'rating'  => 3,
            'comment' => '2回目のレビューです',
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('reviews', 1);
    }
}