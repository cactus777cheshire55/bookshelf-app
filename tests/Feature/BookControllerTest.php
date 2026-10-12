<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 検索フィルターが動作するか(): void
    {
        $user = User::factory()->create();
        $book1 = Book::factory()->create(['title' => 'HelloWorld']);
        $book2 = Book::factory()->create(['title' => 'HelloLaravel']);

        $response = $this->actingAs($user)->get('/?keyword=HelloWorld');

        $response->assertStatus(200);
        $response->assertSee('HelloWorld');
        $response->assertDontSee('HelloLaravel');
    }
    /** @test */
    public function ログインユーザーが書籍を登録できるか(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $payload = [
            'title'          => '「サナエノミクス」で日本は繁栄するのか?!',
            'author'         => '豊島文雄',
            'isbn'           => '9784341088972',
            'published_date' => '2026-10-12',
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
            'genres'         => [$genre->id],
        ];

        $response = $this->actingAs($user)->post('/books', $payload);

        $book = Book::where('isbn', '9784341088972')->first();

        $response->assertRedirect("/books/{$book->id}");
        $response->assertSessionHas('success', '書籍を登録しました');

        $this->assertDatabaseHas('books', [
            'id'      => $book->id,
            'user_id' => $user->id,
            'title'   => '「サナエノミクス」で日本は繁栄するのか?!',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id'  => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    /** @test */
    public function ISBNが重複するとエラーになるか(): void
    {
        $user = User::factory()->create();
        Book::factory()->create(['isbn' => '9784341088972']);

        $payload = [
            'title'  => 'テスト',
            'author' => 'テスト',
            'isbn'   => '9784341088972', 
            'genres' => [],
        ];

        $response = $this->actingAs($user)->post('/books', $payload);

        $response->assertSessionHasErrors(['isbn']);
    }

    /** @test */
    public function 他人の登録した書籍は更新できないかー403エラー(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->for($owner)->create(['title' => 'テスト']);

        $response = $this->actingAs($otherUser)->put("/books/{$book->id}", [
            'title'  => 'テスト',
            'author' => $book->author,
            'isbn'   => $book->isbn,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => 'テスト']);
    }
}
