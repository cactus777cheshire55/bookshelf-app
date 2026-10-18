<?php

namespace Tests\Unit;

use App\Http\Requests\StoreBookRequest;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic unit test example.
     */

    /** @test */
    public function 書籍が作成者に属している(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();

        $this->assertInstanceOf(User::class, $book->user);
        $this->assertEquals($user->id, $book->user->id);
    }

    /** @test */
    public function 書籍が複数のジャンルを持つことが可能か(): void
    {
        $book = Book::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        $book->genres()->attach($genres);

        $this->assertInstanceOf(Collection::class, $book->genres);
        $this->assertCount(2, $book->genres);
        $this->assertTrue($book->genres->contains($genres->first()));
    }

    /** @test */
    public function 書籍が複数のレビューを持つことが可能か(): void
    {
        $book = Book::factory()->create();
        Review::factory()->count(3)->for($book)->create();

        $this->assertInstanceOf(Collection::class, $book->reviews);
        $this->assertCount(3, $book->reviews);
    }

    /** @test */
    public function ユーザーとのリレーションが正しく定義されているか(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $book->user);
        $this->assertEquals($user->id, $book->user->id);
    }

    /** @test */
    public function ジャンルとの多対多リレーションが正しく定義されているか(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $this->assertTrue($book->genres->contains($genre));
    }

    /** @test */
    public function レビューとの一対多リレーションが正しく定義されているか(): void
    {
        $book = Book::factory()->create();
        $review = Review::factory()->create(['book_id' => $book->id]);

        $this->assertTrue($book->reviews->contains($review));
    }

    /** @test */
    public function 出版日が日付型にキャストされているか(): void
    {
        $book = Book::factory()->create([
            'published_date' => '2026-10-18',
        ]);

        $this->assertInstanceOf(Carbon::class, $book->published_date);
        $this->assertEquals('2026-10-18', $book->published_date->format('Y-m-d'));
    }

    /** @test */
    public function 出版日が必須かつdate型になっているか(): void
    {
        $request = new StoreBookRequest;
        $rules = $request->rules();

        $this->assertContains('required', $rules['published_date']);
        $this->assertContains('date', $rules['published_date']);
    }
}
