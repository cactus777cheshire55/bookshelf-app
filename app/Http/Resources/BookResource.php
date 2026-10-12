<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'published_date' => $this->published_date ? $this->published_date->format('Y-m-d') : null,
            'description' => $this->description,
            'image_url' => $this->image_url,

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),

            'genres' => GenreResource::collection($this->whenLoaded('genres')),

            'reviews_avg_rating' => $this->when(
                isset($this->reviews_avg_rating),
                fn () => $this->reviews_avg_rating !== null ? round((float) $this->reviews_avg_rating, 1) : null
            ),

            'reviews_count' => $this->whenCounted('reviews'),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
