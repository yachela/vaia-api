<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'destination' => $this->destination,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'budget' => $this->budget,
            'total_expenses' => $this->total_expenses,
            'activities_count' => $this->whenCounted('activities'),
            'expenses_count' => $this->whenCounted('expenses'),
            'activities' => ActivityResource::collection($this->whenLoaded('activities')),
            'expenses' => ExpenseResource::collection($this->whenLoaded('expenses')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
