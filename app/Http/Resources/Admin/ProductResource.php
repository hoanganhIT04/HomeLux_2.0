<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        $allImages = $this->images
            ->sortByDesc('is_primary')
            ->sortBy('display_order')
            ->pluck('image_url')
            ->map(fn($url) => asset($url))
            ->values()
            ->toArray();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'stock' => $this->quantity,
            'quantity' => $this->quantity,
            'category_ids' => $this->categories->pluck('id')->toArray(),
            'description' => $this->description,
            'sold' => $this->sold ?? 0,
            'image' => $this->primaryImage ? asset($this->primaryImage->image_url) : asset('assets/img/default.jpg'),
            'all_images' => $allImages,
            'model_url' => $this->model_url ? asset($this->model_url) : null,
        ];
    }
}
