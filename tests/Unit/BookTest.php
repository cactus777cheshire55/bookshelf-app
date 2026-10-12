<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

}
