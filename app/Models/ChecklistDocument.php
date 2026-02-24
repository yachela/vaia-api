<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistDocument extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'checklist_documents';

    protected $fillable = [
        'checklist_item_id',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'source',
        'google_drive_file_id',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class, 'checklist_item_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isFromGoogleDrive(): bool
    {
        return $this->source === 'google_drive';
    }

    public function isImage(): bool
    {
        return in_array($this->mime_type, ['image/jpeg', 'image/png', 'image/gif']);
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }
}
