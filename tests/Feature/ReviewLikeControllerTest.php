<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 自分のレビューにはいいねできないか(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post("/reviews/{$review->id}/like");

        $response->assertStatus(302);
        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    /** @test */
    public function 他人のレビューにはいいねの追加・削除ができるか(): void
    {
        $reviewOwner = User::factory()->create();
        $liker = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $reviewOwner->id]);

        $response = $this->actingAs($liker)->post("/reviews/{$review->id}/like");
        $response->assertStatus(302);

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $liker->id,
            'review_id' => $review->id,
        ]);

        $response2 = $this->actingAs($liker)->post("/reviews/{$review->id}/like");
        $response2->assertStatus(302);

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $liker->id,
            'review_id' => $review->id,
        ]);
    }

    /** @test */
    public function 未ログインユーザーはいいねできないか(): void
    {
        $review = Review::factory()->create();

        $response = $this->post("/reviews/{$review->id}/like");

        $response->assertRedirect('/login');
        $this->assertDatabaseMissing('review_likes', [
            'review_id' => $review->id,
        ]);
    }
}
