<?php 

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GoogleBooksService
{
    public function fetchByIsbn(string $isbn): ?array
    {
        $response = Http::get('https://www.googleapis.com/books/v1/volumes', [
            'q' => "isbn:{$isbn}",
        ]);

        if ($response->failed() || ($response->json('totalItems') ?? 0) === 0) {
            return null;
        }

        $item = $response->json('items.0.volumeInfo');

        return [
            'title'          => $item['title'] ?? '',
            'author'         => $item['authors'] ?? '',
            'published_date' => $item['publishedDate'] ?? null,
            'description'    => $item['description'] ?? null,
            'image_url'      => $item['imageLinks']['thumbnail'] ?? null,
        ];
    }
}