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
        $this->service = new GoogleBooksService();
    }

    /** @test */
    public function ISBNでAPIレスポンスが整形されて取得できるか(): void
    {
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 1,
                'items' => [
                    [
                        'volumeInfo' => [
                            'title'         => '「サナエノミクス」で日本は繁栄するのか?!',
                            'authors'       => ['豊島文雄'],
                            'publishedDate' => '2026-10-12',
                            'description'   => '2025年パラダイムシフトの年「ドナルド＆サナエ」2人の指導者が誕生した！
                                                暴走する“マッドマン”大統領＆追従する“縁故主義”女性宰相

                                                第２のジャポニズムが世界をいやす！
                                                日本の文化、四季。日本人の気配り、和の精神。

                                                サナエノミクスの「責任ある積極財政」「成長戦略」、370兆円を費やし「官まかせ」の＜総花的・箱もの＞戦略ではなく,＜日本独自の強み＞に絞り込んだ戦略を断行すべき！― 本文＜第7章＞「高市内閣への提言」より

                                                ─ 主な目次 ─
                                                〈第１部〉世界の秩序と平和を蹂躙する指導者の誕生
                                                第１章:ドナルド・トランプ大統領就任により戦争が日常的に繰り返される世界が始まった ― 世界秩序を破壊する「タリフマン（関税男）」米国第一主義の独裁者 ─
                                                第２章：戦後日本の〝選挙〟と〝政治〟を「革命的」に変えた超保守派の女性宰相の登場 ─ 今すべきは「官」の肥大化をまねく「サナエノミクス」よりも責任ある「行政改革」 ─
                                                〈第２部〉「第1次石油危機」を克服した「民」による行政改革に学べ
                                                第３章:井深大と本田宗一郎の晩年の活躍 ─「日本の自殺」を防いだ行政改革 ─
                                                第４章：日本の鉄道技術の研究開発・調査を担う―鉄道総合技術研究所（JR総研）の初代会長・井深大が鉄道技術の国際化を実現―
                                                第５章： 日本を繁栄させた「変革」リーダー　日本を衰退させたリーダー
                                                〈第３部〉軍事大国による暗黒時代 VS 日本文化共有主義が世界をいやす
                                                第６章：日本文化の「気配り」と「倦まず弛まず」が世界をいやす
                                                第７章：高市内閣への提言',
                            'imageLinks'    => [
                            'thumbnail' => 'https://books.google.com/thumbnail.jpg',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->fetchByIsbn('9784341088972');

        $this->assertNotNull($result);
        $this->assertEquals('「サナエノミクス」で日本は繁栄するのか?!', $result['title']);
        $this->assertEquals(['豊島文雄'], $result['author']);
        $this->assertEquals('2026-10-12', $result['published_date']);
        $this->assertEquals('https://books.google.com/thumbnail.jpg', $result['image_url']);
    }

    /** @test */
    public function 存在しないISBNを入力したらnullが返るか(): void
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
    public function API通信障害発生時ー500エラー、nullが返るか(): void
    {
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([], 500),
        ]);

        $result = $this->service->fetchByIsbn('9784341088972');

        $this->assertNull($result);
    }
}