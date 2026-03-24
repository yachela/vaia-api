<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackingItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'packing_list_id',
        'name',
        'category',
        'is_packed',
        'is_suggested',
        'suggestion_reason',
    ];

    protected $casts = [
        'is_packed' => 'boolean',
        'is_suggested' => 'boolean',
    ];

    public function packingList(): BelongsTo
    {
        return $this->belongsTo(PackingList::class);
    }
}
