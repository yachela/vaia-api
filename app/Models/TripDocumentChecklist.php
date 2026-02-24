<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TripDocumentChecklist extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'trip_document_checklists';

    protected $fillable = [
        'trip_id',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ChecklistItem::class)->orderBy('position');
    }

    public function getCompletedCountAttribute(): int
    {
        return $this->items()->where('is_completed', true)->count();
    }

    public function getTotalCountAttribute(): int
    {
        return $this->items()->count();
    }

    public function getProgressAttribute(): array
    {
        return [
            'completed' => $this->completed_count,
            'total' => $this->total_count,
            'percentage' => $this->total_count > 0
                ? (int) (($this->completed_count / $this->total_count) * 100)
                : 0,
        ];
    }
}
