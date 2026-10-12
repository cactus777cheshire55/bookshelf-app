<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ap_i書籍一覧取得できるか(): void
    {
        Book::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'title', 'author', 'isbn', 'published_date',
                        'description', 'image_url', 'genres', 'reviews_count',
                        'created_at', 'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    /** @test */
    public function 書籍詳細が取得できるか(): void
    {
        $book = Book::factory()->create([
            'title' => 'API詳細テスト本',
            'author' => 'テスト著者',
            'isbn' => '9999999999989',
        ]);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 'title', 'author', 'isbn', 'published_date',
                    'description', 'image_url', 'genres', 'reviews_count',
                    'created_at', 'updated_at',
                ],
            ])
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.title', 'API詳細テスト本');
    }

    /** @test */
    public function ログインユーザーは書籍作成できるか(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $payload = [
            'title' => 'API登録本',
            'author' => 'API著者',
            'isbn' => '9999999999990',
            'published_date' => '2026-10-12',
            'description' => 'API経由でのテスト説明文です。',
            'genres' => [$genre->id],
        ];

        $response = $this->postJson('/api/v1/books', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'API登録本');

        $this->assertDatabaseHas('books', ['isbn' => '9999999999990']);
    }

    /** @test */
    public function 未ログインユーザーは書籍作成できないか(): void
    {
        $response = $this->postJson('/api/v1/books', [
            'title' => '未許可の書籍',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function 自分の書籍は更新ができるか(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '古いAPI本のタイトル',
        ]);

        Sanctum::actingAs($user, ['*']);

        $payload = [
            'title' => '新しいAPI本のタイトル',
            'author' => '更新著者',
            'isbn' => $book->isbn,
            'published_date' => '2026-10-12',
            'description' => '更新されたAPI説明文です。',
            'genres' => [$genre->id],
        ];

        $response = $this->putJson("/api/v1/books/{$book->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', '新しいAPI本のタイトル');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '新しいAPI本のタイトル',
        ]);
    }

    /** @test */
    public function 他人の書籍は更新できないか(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '元のAPI本',
        ]);

        Sanctum::actingAs($otherUser, ['*']);

        $payload = [
            'title' => '更新用のタイトル',
            'author' => $book->author,
            'isbn' => $book->isbn,
            'published_date' => '2026-10-12',
            'description' => '勝手に書き換えます。',
            'genres' => [],
        ];
        $response = $this->putJson("/api/v1/books/{$book->id}", $payload);

        $response->assertStatus(403);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '元のAPI本',
        ]);
    }
}
