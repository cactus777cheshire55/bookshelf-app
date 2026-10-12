<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 未ログイン時の画面アクセス(): void
    {
        $book = Book::factory()->create();

        $this->get('/login')->assertStatus(200);
        $this->get('/register')->assertStatus(200);
        $this->get('/')->assertStatus(200);
        $this->get('/books')->assertStatus(200);
        $this->get("/books/{$book->id}")->assertStatus(200);
        $this->get('/ranking')->assertStatus(200);

        $protectedUrls = [
            '/books/create',
            '/genres',
            '/genres/create',
            '/favorites',
            '/reports',
            '/reading-plans',
            '/reading-plans/create',
            '/notifications',
        ];

        foreach ($protectedUrls as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    /** @test */
    public function ログイン済みユーザーが正常アクセスできるか(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $genre = Genre::factory()->create();
        $book = Book::factory()->for($user)->create();
        $review = Review::factory()->for($user)->for($book)->create();
        $plan = ReadingPlan::factory()->for($user)->for($book)->create();

        $this->actingAs($user)->get('/')->assertStatus(200);
        $this->actingAs($user)->get('/books')->assertStatus(200);
        $this->actingAs($user)->get("/books/{$book->id}")->assertStatus(200);
        $this->actingAs($user)->get('/books/create')->assertStatus(200);
        $this->actingAs($user)->get("/books/{$book->id}/edit")->assertStatus(200);

        $this->actingAs($user)->get('/genres')->assertStatus(200);
        $this->actingAs($user)->get("/genres/{$genre->id}")->assertStatus(200);
        $this->actingAs($user)->get('/genres/create')->assertStatus(200);
        $this->actingAs($user)->get("/genres/{$genre->id}/edit")->assertStatus(200);

        $this->actingAs($user)->get("/reviews/{$review->id}/edit")->assertStatus(200);
        $this->actingAs($user)->get('/favorites')->assertStatus(200);
        $this->actingAs($user)->get('/ranking')->assertStatus(200);

        $this->actingAs($user)->get('/reports')->assertStatus(200);

        $this->actingAs($user)->get('/reading-plans')->assertStatus(200);
        $this->actingAs($user)->get('/reading-plans/create')->assertStatus(200);
        $this->actingAs($user)->get("/reading-plans/{$plan->id}/edit")->assertStatus(200);

        $this->actingAs($user)->get('/notifications')->assertStatus(200);
    }
}
