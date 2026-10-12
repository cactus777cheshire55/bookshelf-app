<?php

namespace Tests\Unit\Services;

use App\Services\GoogleBooksService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleBooksServiceTest extends TestCase
{
    private GoogleBooksService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GoogleBooksService;
    }

    /** @test */
    public function isb_nで_ap_iレスポンスが整形されて取得できるか(): void
    {
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 1,
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => '「サナエノミクス」で日本は繁栄するのか?!',
                            'authors' => '[豊島文雄]',
                            'publishedDate' => '2026-10-12',
                            'description' => 'テスト説明文',
                            'imageLinks' => [
                                'thumbnail' => 'http://google.com',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->fetchByIsbn('9784341088972');

        $this->assertNotNull($result);
        $this->assertEquals('「サナエノミクス」で日本は繁栄するのか?!', $result['title']);

        $this->assertEquals('[豊島文雄]', $result['author']);
        $this->assertEquals('2026-10-12', $result['published_date']);

        $this->assertEquals('http://google.com', $result['image_url']);
    }

    /** @test */
    public function 存在しない_isb_nを入力したらnullが返るか(): void
    {
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 0,
            ], 200),
        ]);

        $result = $this->service->fetchByIsbn('9999999999999');

        $this->assertNull($result);
    }

    /** @test */
    public function ap_i通信障害発生時ー500エラー、nullが返るか(): void
    {
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([], 500),
        ]);

        $result = $this->service->fetchByIsbn('9784341088972');

        $this->assertNull($result);
    }
}
