<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChecklistDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'checklist_item_id' => $this->checklist_item_id,
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'source' => $this->source,
            'google_drive_file_id' => $this->google_drive_file_id,
            'uploaded_by' => $this->uploaded_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
