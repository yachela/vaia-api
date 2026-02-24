<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChecklistItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'checklist_items';

    protected $fillable = [
        'trip_document_checklist_id',
        'name',
        'is_default',
        'is_completed',
        'position',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_completed' => 'boolean',
        'position' => 'integer',
    ];

    public const DEFAULT_CATEGORIES = [
        'Passport',
        'Visa',
        'Travel insurance',
        'Flight reservation',
        'Hotel reservation',
        'Itinerary',
        'Medical/vaccination certificates',
        'Additional ID documents',
    ];

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(TripDocumentChecklist::class, 'trip_document_checklist_id');
    }

    public function document(): HasOne
    {
        return $this->hasOne(ChecklistDocument::class, 'checklist_item_id');
    }

    public function markAsComplete(): void
    {
        $this->update(['is_completed' => true]);
    }

    public function markAsIncomplete(): void
    {
        $this->update(['is_completed' => false]);
    }

    public function toggleCompletion(): void
    {
        $this->update(['is_completed' => ! $this->is_completed]);
    }
}
