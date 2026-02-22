<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'trip_id',
        'user_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'description',
    ];

    /**
     * Get the trip that owns the Document.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the user that owns the Document.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
