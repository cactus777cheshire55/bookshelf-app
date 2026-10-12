<?php 

namespace Tests\Unit\Policies;

use App\Models\Book;
use App\Models\User;
use App\Policies\BookPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookPolicyTest extends TestCase
{
    use RefreshDatabase;

    private BookPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new BookPolicy();
    }

    /** @test */
    public function 作者本人が更新できるか(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $this->assertTrue($this->policy->update($owner, $book));
    }

    /** @test */
    public function 作者以外の更新は拒否されるか(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $this->assertFalse($this->policy->update($otherUser, $book));
    }

    /** @test */
    public function 作者本人は書籍の削除ができるか(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $this->assertTrue($this->policy->delete($owner, $book));
    }

    /** @test */
    public function 作者以外は書籍の削除が拒否されるか(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $this->assertFalse($this->policy->delete($otherUser, $book));
    }
}