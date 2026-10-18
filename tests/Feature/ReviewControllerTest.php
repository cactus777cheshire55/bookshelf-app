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
    public function ログインユーザーはレビューを投稿できるか(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $payload = [
            'rating' => 5,
            'comment' => '素晴らしい',
        ];

        $response = $this->actingAs($user)->post("/books/{$book->id}/reviews", $payload);

        $response->assertRedirect("/books/{$book->id}");
        $response->assertSessionHas('success', 'レビューを投稿しました。');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '素晴らしい',
        ]);
    }

    /** @test */
    public function 評価が未選択の場合はバリデーションエラーになるか(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $payload = [
            'rating' => null,
            'comment' => 'コメントのみ',
        ];

        $response = $this->actingAs($user)->post("/books/{$book->id}/reviews", $payload);

        $response->assertSessionHasErrors(['rating']);
        $this->assertDatabaseMissing('reviews', ['user_id' => $user->id, 'book_id' => $book->id]);
    }

    /** @test */
    public function 一から五以外の評価はバリデーションエラーになるか(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post("/books/{$book->id}/reviews", [
            'rating' => 6,
            'comment' => '不正な値',
        ]);

        $response->assertSessionHasErrors(['rating']);
    }

    /** @test */
    public function 二回目のレビューを投稿することはできないか(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
        ]);

        $response = $this->actingAs($user)->post("/books/{$book->id}/reviews", [
            'rating' => 5,
            'comment' => '2回目の投稿',
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('reviews', 1);
    }

    /** @test */
    public function 投稿者本人は編集画面にアクセスできるか(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->for($user)->create();

        $response = $this->actingAs($user)->get("/reviews/{$review->id}/edit");

        $response->assertStatus(200);
        $response->assertSee($review->comment);
    }

    /** @test */
    public function 投稿者本人はレビューを更新できるか(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->for($user)->create([
            'rating' => 3,
            'comment' => '更新前',
        ]);

        $payload = [
            'rating' => 4,
            'comment' => '更新後',
        ];

        $response = $this->actingAs($user)->put("/reviews/{$review->id}", $payload);

        $response->assertRedirect("/books/{$review->book_id}");
        $response->assertSessionHas('success', 'レビューを更新しました。');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 4,
            'comment' => '更新後',
        ]);
    }

    /** @test */
    public function 他人のレビュー編集画面にはアクセスできないか_403_forbidden(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $review = Review::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)->get("/reviews/{$review->id}/edit");

        $response->assertStatus(403);
    }

    /** @test */
    public function 他人のレビューを更新することはできないか_403_forbidden(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $review = Review::factory()->for($owner)->create(['comment' => '元の評価']);

        $response = $this->actingAs($otherUser)->put("/reviews/{$review->id}", [
            'rating' => 1,
            'comment' => '他人です',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'comment' => '元の評価',
        ]);
    }

    /** @test */
    public function 投稿者本人は自分のレビューを削除できるか(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete("/reviews/{$review->id}");

        $response->assertRedirect("/books/{$review->book_id}");
        $response->assertSessionHas('success', 'レビューを削除しました。');

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    /** @test */
    public function 他人のレビューを削除することはできないか_403_forbidden(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $review = Review::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)->delete("/reviews/{$review->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }

    /** @test */
    public function 未ログイン時はレビュー投稿や編集ができない(): void
    {
        $book = Book::factory()->create();
        $review = Review::factory()->create();

        $this->post("/books/{$book->id}/reviews", ['rating' => 1])
            ->assertRedirect('/login');

        $this->get("/reviews/{$review->id}/edit")
            ->assertRedirect('/login');

        $this->delete("/reviews/{$review->id}")
            ->assertRedirect('/login');
    }

    /** @test */
    public function コメントが1000文字を超える場合はエラーになるか(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->postJson("/books/{$book->id}/reviews", [
            'rating' => 5,
            'comment' => str_repeat('あ', 1001), // ★ 256文字 -> 1001文字に変更
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['comment']);
    }
}
