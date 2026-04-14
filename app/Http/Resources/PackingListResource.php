<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackingListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $itemsByCategory = $this->items->groupBy('category');
        $totalItems = $this->items->count();
        $packedItems = $this->items->where('is_packed', true)->count();

        return [
            'id' => $this->id,
            'trip_id' => $this->trip_id,
            'status' => $this->status,
            'items_by_category' => $itemsByCategory->map(function ($items, $category) {
                return [
                    'category' => $category,
                    'items' => PackingItemResource::collection($items),
                ];
            })->values(),
            'progress' => [
                'total' => $totalItems,
                'packed' => $packedItems,
                'percentage' => $totalItems > 0 ? round(($packedItems / $totalItems) * 100) : 0,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
