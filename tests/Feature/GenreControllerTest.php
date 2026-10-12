<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 書籍が紐づいてるジャンルは削除できないか(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();
        $book->genres()->attach($genre);

        $response = $this->actingAs($user)->delete("/genres/{$genre->id}");

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('genres', ['id' => $genre->id]);
    }

    /** @test */
    public function ログインユーザーはジャンル名を更新できるか(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '古いジャンル名']);

        $payload = ['name' => '新しいジャンル名'];
        $response = $this->actingAs($user)->put("/genres/{$genre->id}", $payload);

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'id'   => $genre->id,
            'name' => '新しいジャンル名',
        ]);
    }

    /** @test */
    public function ログインユーザーはジャンルを新規登録できるか(): void
    {
        $user = User::factory()->create();
        $payload = ['name' => 'テスト用ジャンル'];
        $response = $this->actingAs($user)->post('/genres', $payload);

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'name' => 'テスト用ジャンル',
        ]);
    }
}