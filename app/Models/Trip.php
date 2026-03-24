<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trip extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'destination',
        'start_date',
        'end_date',
        'budget',
        'trip_type',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function documentChecklist(): HasOne
    {
        return $this->hasOne(TripDocumentChecklist::class);
    }

    public function packingList(): HasOne
    {
        return $this->hasOne(PackingList::class);
    }

    public function getTotalExpensesAttribute(): float
    {
        return $this->expenses()->sum('amount');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now());
    }
}
