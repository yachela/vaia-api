<?php

namespace App\Services;

use App\Models\ChecklistDocument;
use App\Models\ChecklistItem;
use App\Models\TripDocumentChecklist;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChecklistService
{
    public function addItem(TripDocumentChecklist $checklist, string $name, int $position): ChecklistItem
    {
        return ChecklistItem::create([
            'trip_document_checklist_id' => $checklist->id,
            'name' => $name,
            'is_default' => false,
            'is_completed' => false,
            'position' => $position,
        ]);
    }

    public function uploadDocument(ChecklistItem $item, UploadedFile $file, User $user): ChecklistDocument
    {
        if ($item->document) {
            $this->deleteDocumentFile($item->document);
        }

        $tripUuid = $item->checklist->trip_id;
        $itemUuid = $item->id;
        $filename = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $storedFilename = Str::uuid().'.'.$extension;

        $path = $file->storeAs("documents/{$tripUuid}/{$itemUuid}", $storedFilename, 'private');

        return ChecklistDocument::create([
            'checklist_item_id' => $item->id,
            'file_name' => $filename,
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'source' => 'local',
            'uploaded_by' => $user->id,
        ]);
    }

    public function importFromGoogleDrive(
        ChecklistItem $item,
        string $fileId,
        string $accessToken,
        User $user
    ): ChecklistDocument {
        if ($item->document) {
            $this->deleteDocumentFile($item->document);
        }

        $googleDriveService = new GoogleDriveService;
        $fileContent = $googleDriveService->getFileContent($fileId, $accessToken);

        $fileMetadata = $googleDriveService->getFileMetadata($fileId, $accessToken);
        $filename = $fileMetadata['name'] ?? 'document.pdf';
        $mimeType = $fileMetadata['mimeType'] ?? 'application/pdf';

        $tripUuid = $item->checklist->trip_id;
        $itemUuid = $item->id;
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $storedFilename = Str::uuid().'.'.$extension;

        $path = "documents/{$tripUuid}/{$itemUuid}/{$storedFilename}";
        Storage::disk('private')->put($path, $fileContent);

        return ChecklistDocument::create([
            'checklist_item_id' => $item->id,
            'file_name' => $filename,
            'file_path' => $path,
            'mime_type' => $mimeType,
            'file_size' => strlen($fileContent),
            'source' => 'google_drive',
            'google_drive_file_id' => $fileId,
            'uploaded_by' => $user->id,
        ]);
    }

    public function deleteDocumentFile(ChecklistDocument $document): void
    {
        try {
            Storage::disk('private')->delete($document->file_path);
            Log::info('Document file deleted', ['file_path' => $document->file_path]);
        } catch (\Exception $e) {
            Log::error('Failed to delete document file', [
                'file_path' => $document->file_path,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
