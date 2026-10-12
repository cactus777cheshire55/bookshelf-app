<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 未ログインに関わらずランキング画面を表示できるか(): void
    {
        $response = $this->get('/ranking');

        $response->assertStatus(200);
    }

    /** @test */
    public function レビューの平均評価が高い順_to_p10が表示されるか(): void
    {
        $rank1Book = Book::factory()->create(['title' => '第1位の書籍']);
        Review::factory()->create(['book_id' => $rank1Book->id, 'rating' => 5]);

        $rank2Book = Book::factory()->create(['title' => '第2位の書籍']);
        Review::factory()->create(['book_id' => $rank2Book->id, 'rating' => 3]);

        $response = $this->get('/ranking');

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            '第1位の書籍',
            '第2位の書籍',
        ]);
    }
}
