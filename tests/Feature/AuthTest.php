<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 未ログインユーザーが書籍作成画面にアクセスするとログインが面にリダイレクトされるか(): void
    {
        $response = $this->get('/books/create');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function ユーザー情報があるユーザーがログイン出来るか(): void
    {
        $user = User::factory()->create([
            'email'    => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email'    => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/books'); 
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function パスワードが符合しない場合はログインできないか(): void
    {
        $user = User::factory()->create([
            'email'    => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email'    => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
