<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ジャンルが複数の書籍と多対多で紐づいているか(): void
    {
        $genre = Genre::factory()->create();
        $books = Book::factory()->count(2)->create();

        $genre->books()->attach($books);

        $this->assertInstanceOf(Collection::class, $genre->books);
        $this->assertCount(2, $genre->books);
        $this->assertTrue($genre->books->contains($books->first()));
    }
}
