<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function お気に入りの追加と削除_トグル処理_ができるか(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post("/books/{$book->id}/favorites");

        $response->assertStatus(302);
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response2 = $this->actingAs($user)->post("/books/{$book->id}/favorites");

        $response2->assertStatus(302);
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    /** @test */
    public function お気に入り一覧画面に登録した書籍が表示されるか(): void
    {
        $user = User::factory()->create();
        $favoriteBook = Book::factory()->create(['title' => 'お気に入り登録本']);
        $otherBook = Book::factory()->create(['title' => '未登録本']);

        $user->favoriteBooks()->attach($favoriteBook);

        $response = $this->actingAs($user)->get('/favorites');

        $response->assertStatus(200);
        $response->assertSee('お気に入り登録本');
        $response->assertDontSee('未登録本');
    }

    /** @test */
    public function 未ログインユーザーはお気に入り操作や一覧アクセスができないか(): void
    {
        $book = Book::factory()->create();

        $this->post("/books/{$book->id}/favorites")
            ->assertRedirect('/login');

        $this->get('/favorites')
            ->assertRedirect('/login');
    }
}
