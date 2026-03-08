<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'trip_id',
        'amount',
        'description',
        'date',
        'category',
        'receipt_image',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function getReceiptImageUrlAttribute(): ?string
    {
        if (! $this->receipt_image) {
            return null;
        }

        return route('api.expenses.receipt', [
            'trip' => $this->trip_id,
            'expense' => $this->id,
        ]);
    }
}
