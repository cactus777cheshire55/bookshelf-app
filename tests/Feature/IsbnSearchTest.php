<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IsbnSearchTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function isb_n検索_13桁の半角数字以外は400エラーになるか(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/books/isbn/123456789012');
        $response->assertStatus(400)
            ->assertJson(['error' => 'ISBN-13の値が不正です']);

        $response = $this->actingAs($user)->getJson('/books/isbn/978434108897A');
        $response->assertStatus(400)
            ->assertJson(['error' => 'ISBN-13の値が不正です']);
    }

    /** @test */
    public function isb_n検索でデータを取得しフォーマット変換されるか(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://api.openbd.jp/*' => Http::response([
                [
                    'summary' => [
                        'title' => 'タイトル',
                        'author' => '著者',
                        'pubdate' => '20261012',
                        'cover' => 'http://openbd.com/cover.jpg',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->getJson('/books/isbn/9784341088972');

        $response->assertStatus(200)
            ->assertJson([
                'title' => 'タイトル',
                'author' => '著者',
                'published_date' => '2026-10-12',
                'image_url' => 'https://openbd.com/cover.jpg',
            ]);
    }

    /** @test */
    public function isb_n検索_open_b_dでヒットしない場合_google_books_ap_iにフォールバックされるか(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://api.openbd.jp/*' => Http::response([null], 200),

            '*googleapis.com*' => Http::response([
                'totalItems' => 1,
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'GoogleBooks側のタイトル',
                            'authors' => ['GoogleBooks著者'],
                            'publishedDate' => '2025-05-01',
                            'description' => 'GoogleBooksの説明文',
                            'imageLinks' => [
                                'thumbnail' => 'http://books.google.com/thumbnail.jpg',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->getJson('/books/isbn/9784341088972');

        $response->assertStatus(200)
            ->assertJson([
                'title' => 'GoogleBooks側のタイトル',
                'author' => 'GoogleBooks著者',
                'published_date' => '2025-05-01',
                'image_url' => 'https://books.google.com/thumbnail.jpg',
            ]);
    }

    /** @test */
    public function isb_n検索_両方の_ap_iでヒットしない場合は404エラーになるか(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://api.openbd.jp/*' => Http::response([null], 200),
            '*googleapis.com*' => Http::response(['totalItems' => 0], 200),
        ]);

        $response = $this->actingAs($user)->getJson('/books/isbn/9784341088972');

        $response->assertStatus(404)
            ->assertJson(['error' => '該当する書籍が見つかりませんでした。']);
    }

    /** @test */
    public function 未ログインユーザーは_isb_n検索を利用できないか(): void
    {
        $response = $this->getJson('/books/isbn/9784341088972');

        $response->assertStatus(401);
    }

    /** @test */
    public function isb_n検索_外部_ap_i通信障害発生時はエラーを返すか(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://api.openbd.jp/*' => Http::response([], 500),
            '*googleapis.com*' => Http::response([], 500),
        ]);

        $response = $this->actingAs($user)->getJson('/books/isbn/9784341088972');

        $response->assertStatus(404)
            ->assertJson(['error' => '該当する書籍が見つかりませんでした。']);
    }

    /** @test */
    public function ISBN検索_説明が240文字を超える場合は237文字でカットされドットが付与されるか(): void
    {
        Http::fake([
            '*' => Http::response([
                [
                    'summary' => [
                        'title'  => 'テスト本',
                        'author' => 'テスト著者',
                    ],
                    'onix' => [
                        'CollateralDetail' => [
                            'TextContent' => [
                                ['Text' => str_repeat('あ', 300)] 
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/books/isbn/9784341088972');

        $response->assertStatus(200);

        $responseData = $response->json();

        $this->assertEquals(240, mb_strlen($responseData['description']));
        $this->assertTrue(str_ends_with($responseData['description'], '...'));
        
        $expectedContent = str_repeat('あ', 237) . '...';
        $this->assertEquals($expectedContent, $responseData['description']);
    }
}
