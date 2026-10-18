<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ログインユーザーは書籍一覧画面を表示できるか(): void
    {
        $user = User::factory()->create();
        Book::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/books');

        $response->assertStatus(200);
    }

    /** @test */
    public function ログインユーザーは書籍登録画面を表示できるか(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/books/create');

        $response->assertStatus(200);
    }

    /** @test */
    public function 新規書籍を登録できるか(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $payload = [
            'title' => 'Raspberry Piではじめる IoT×Webシステム開発入門',
            'author' => '小笠原祐治',
            'isbn' => '9784627858817',
            'published_date' => '2026-01-01',
            'description' => '',
            'image_url' => 'https://placehold.co/200x300',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)->post('/books', $payload);

        $book = Book::where('isbn', '9784627858817')->first();

        $response->assertRedirect("/books/{$book->id}");
        $response->assertSessionHas('success', '書籍を登録しました');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => 'Raspberry Piではじめる IoT×Webシステム開発入門',
            'isbn' => '9784627858817',
        ]);
        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    /** @test */
    public function 必須項目が未入力の場合はバリデーションエラーになるか(): void
    {
        $user = User::factory()->create();

        $payload = [
            'title' => '',
            'author' => '',
            'isbn' => '',
        ];

        $response = $this->actingAs($user)->post('/books', $payload);

        $response->assertSessionHasErrors(['title', 'author', 'isbn']);
        $this->assertDatabaseCount('books', 0);
    }

    /** @test */
    public function 書籍詳細画面が表示されるか(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'title' => 'テスト対象書籍',
            'author' => 'テスト著者',
        ]);

        $response = $this->actingAs($user)->get("/books/{$book->id}");

        $response->assertStatus(200);
        $response->assertSee('テスト対象書籍');
        $response->assertSee('テスト著者');
    }

    /** @test */
    public function 投稿者本人は書籍編集画面を表示できるか(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();

        $response = $this->actingAs($user)->get("/books/{$book->id}/edit");

        $response->assertStatus(200);
        $response->assertSee($book->title);
    }

    /** @test */
    public function 投稿者本人は書籍情報を更新できるか(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create(['title' => '変更前']);
        $genre = Genre::factory()->create();
        $payload = [
            'title' => '変更後',
            'author' => $book->author,
            'isbn' => $book->isbn,
            'genres' => [$genre->id],
            'published_date' => now()->addDays(1)->format('Y-m-d'),
        ];

        $response = $this->actingAs($user)->put("/books/{$book->id}", $payload);

        $response->assertRedirect("/books/{$book->id}");
        $response->assertSessionHas('success', '書籍情報を更新しました。');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '変更後',
        ]);
    }

    /** @test */
    public function 他人の書籍は更新できないか_403_forbidden(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->for($owner)->create(['title' => '元のタイトル']);

        $response = $this->actingAs($otherUser)->put("/books/{$book->id}", [
            'title' => '不正',
            'author' => $book->author,
            'isbn' => $book->isbn,
            'published_date' => now()->addDays(1)->format('Y-m-d'),
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '元のタイトル']);
    }

    /** @test */
    public function 投稿者本人は書籍を削除できるか(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete("/books/{$book->id}");

        $response->assertRedirect('/');
        $response->assertSessionHas('success', '書籍を削除しました');

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    /** @test */
    public function 他人の登録した書籍は削除できないか_403_forbidden(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)->delete("/books/{$book->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }

    /** @test */
    public function タイトル、著者名で検索絞り込みができるか(): void
    {
        $user = User::factory()->create();

        $book1 = Book::factory()->create(['title' => 'HalloWorld', 'author' => '山田太郎']);
        $book2 = Book::factory()->create(['title' => 'HalloLaravel', 'author' => '鈴木花子']);

        $response1 = $this->actingAs($user)->get('/books?keyword=World');
        $response1->assertStatus(200);
        $response1->assertSee('HalloWorld');
        $response1->assertDontSee('HalloLaravel');

        $response2 = $this->actingAs($user)->get('/books?keyword=鈴木');
        $response2->assertStatus(200);
        $response2->assertSee('HalloLaravel');
        $response2->assertDontSee('HalloWorld');
    }

    /** @test */
    public function ジャンル指定で書籍を絞り込みできるか(): void
    {
        $user = User::factory()->create();

        $genreTech = Genre::factory()->create(['name' => '技術書']);
        $genreNovel = Genre::factory()->create(['name' => '小説']);

        $bookTech = Book::factory()->create(['title' => 'Raspberry Piクックブック 第4版']);
        $bookTech->genres()->attach($genreTech);

        $bookNovel = Book::factory()->create(['title' => '銀河鉄道の夜']);
        $bookNovel->genres()->attach($genreNovel);

        $response = $this->actingAs($user)->get("/books?genre={$genreTech->id}");

        $response->assertStatus(200);
        $response->assertSee('Raspberry Piクックブック 第4版');
        $response->assertDontSee('銀河鉄道の夜');
    }

    /** @test */
    public function 登録日時の新しい順でソートできるか(): void
    {
        $user = User::factory()->create();

        $oldBook = Book::factory()->create(['title' => '古い本', 'created_at' => now()->subDays(2)]);
        $newBook = Book::factory()->create(['title' => '新しい本', 'created_at' => now()]);

        $response = $this->actingAs($user)->get('/books?sort=latest');

        $response->assertStatus(200);
        $response->assertSeeInOrder(['新しい本', '古い本']);
    }

    /** @test */
    public function 登録日時の古い順でソートできるか(): void
    {
        $user = User::factory()->create();

        $oldBook = Book::factory()->create(['title' => '古い本', 'created_at' => now()->subDays(2)]);
        $newBook = Book::factory()->create(['title' => '新しい本', 'created_at' => now()]);

        $response = $this->actingAs($user)->get('/books?sort=oldest');

        $response->assertStatus(200);
        $response->assertSeeInOrder(['古い本', '新しい本']);
    }

    /** @test */
    public function タイトルの五十音_アルファベット昇順でソートできるか(): void
    {
        $user = User::factory()->create();

        $bookA = Book::factory()->create(['title' => 'Abook']);
        $bookB = Book::factory()->create(['title' => 'Bbook']);

        $response = $this->actingAs($user)->get('/books?sort=title');

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Abook', 'Bbook']);
    }

    /** @test */
    public function レビュー平均評価が高い順にソートできるか(): void
    {
        $user = User::factory()->create();

        $highRatedBook = Book::factory()->create(['title' => '高評価本']);
        Review::factory()->create(['book_id' => $highRatedBook->id, 'rating' => 5]);

        $lowRatedBook = Book::factory()->create(['title' => '低評価本']);
        Review::factory()->create(['book_id' => $lowRatedBook->id, 'rating' => 2]);

        $noReviewBook = Book::factory()->create(['title' => 'レビューなし本']);

        $response = $this->actingAs($user)->get('/books?sort=rating');

        $response->assertStatus(200);
        $response->assertSeeInOrder(['高評価本', '低評価本', 'レビューなし本']);
    }
}
