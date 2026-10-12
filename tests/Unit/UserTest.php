<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ユーザーが複数の書籍を登録できるか(): void
    {
        $user = User::factory()->create();
        Book::factory()->count(2)->for($user)->create();

        $this->assertInstanceOf(Collection::class, $user->books);
        $this->assertCount(2, $user->books);
    }

    /** @test */
    public function ユーザーが複数のレビューを投稿できるか(): void
    {
        $user = User::factory()->create();
        Review::factory()->count(2)->for($user)->create();

        $this->assertInstanceOf(Collection::class, $user->reviews);
        $this->assertCount(2, $user->reviews);
    }

    /** @test */
    public function ユーザーが複数の書籍をお気に入り登録できる(): void
    {
        $user = User::factory()->create();
        $books = Book::factory()->count(2)->create();

        $user->favoriteBooks()->attach($books);

        $this->assertInstanceOf(Collection::class, $user->favoriteBooks);
        $this->assertCount(2, $user->favoriteBooks);
        $this->assertTrue($user->favoriteBooks->contains($books->first()));
    }

    /** @test */
    public function ユーザーが複数の読書計画を持てるか(): void
    {
        $user = User::factory()->create();
        ReadingPlan::factory()->count(2)->for($user)->create();

        $this->assertInstanceOf(Collection::class, $user->readingPlans);
        $this->assertCount(2, $user->readingPlans);
    }

    /** @test */
    public function ユーザーが複数のレビューに「いいね」できるか(): void
    {
        $user = User::factory()->create();
        $reviews = Review::factory()->count(2)->create();

        $user->likedReviews()->attach($reviews);

        $this->assertInstanceOf(Collection::class, $user->likedReviews);
        $this->assertCount(2, $user->likedReviews);
        $this->assertTrue($user->likedReviews->contains($reviews->first()));
    }
}
