<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChecklistItemResource;
use App\Http\Resources\TripDocumentChecklistResource;
use App\Models\ChecklistDocument;
use App\Models\ChecklistItem;
use App\Models\Trip;
use App\Models\TripDocumentChecklist;
use App\Services\ChecklistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChecklistController extends Controller
{
    public function __construct(
        protected ChecklistService $checklistService
    ) {}

    public function show(Trip $trip): JsonResponse
    {
        try {
            $this->authorize('view', $trip);

            $checklist = $trip->documentChecklist()->with('items.document')->first();

            if (! $checklist) {
                $checklist = TripDocumentChecklist::create([
                    'trip_id' => $trip->id,
                ]);
                $checklist = $checklist->fresh()->load('items.document');
            }

            return response()->json([
                'data' => new TripDocumentChecklistResource($checklist),
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching checklist', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Error fetching checklist'], 500);
        }
    }

    public function addItem(Request $request, Trip $trip): JsonResponse
    {
        try {
            $this->authorize('view', $trip);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $checklist = $trip->documentChecklist;

            if (! $checklist) {
                $checklist = TripDocumentChecklist::create([
                    'trip_id' => $trip->id,
                ]);
            }

            $position = $checklist->items()->max('position') ?? 0;

            $item = $this->checklistService->addItem($checklist, $validated['name'], $position + 1);

            return response()->json([
                'data' => new ChecklistItemResource($item),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error adding checklist item', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Error adding item'], 500);
        }
    }

    public function toggleComplete(Request $request, ChecklistItem $item): JsonResponse
    {
        try {
            $validated = $request->validate([
                'is_completed' => 'required|boolean',
            ]);

            $item->update(['is_completed' => $validated['is_completed']]);

            return response()->json([
                'data' => new ChecklistItemResource($item->fresh()),
            ]);
        } catch (\Exception $e) {
            Log::error('Error toggling item completion', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Error updating item'], 500);
        }
    }

    public function deleteItem(ChecklistItem $item): JsonResponse
    {
        try {
            if ($item->document) {
                $this->checklistService->deleteDocumentFile($item->document);
            }

            $item->delete();

            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('Error deleting checklist item', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Error deleting item'], 500);
        }
    }

    public function uploadDocument(Request $request, ChecklistItem $item): JsonResponse
    {
        try {
            $validated = $request->validate([
                'file' => 'required|file|max:25600|mimetypes:application/pdf,image/jpeg,image/png,image/gif,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);

            $document = $this->checklistService->uploadDocument(
                $item,
                $request->file('file'),
                auth()->user()
            );

            $item->update(['is_completed' => true]);

            return response()->json([
                'data' => new \App\Http\Resources\ChecklistDocumentResource($document),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error uploading document', ['error' => $e->getMessage()]);

            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function importFromDrive(Request $request, ChecklistItem $item): JsonResponse
    {
        try {
            $validated = $request->validate([
                'file_id' => 'required|string',
                'access_token' => 'required|string',
            ]);

            $document = $this->checklistService->importFromGoogleDrive(
                $item,
                $validated['file_id'],
                $validated['access_token'],
                auth()->user()
            );

            $item->update(['is_completed' => true]);

            return response()->json([
                'data' => new \App\Http\Resources\ChecklistDocumentResource($document),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error importing from Google Drive', ['error' => $e->getMessage()]);

            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function downloadDocument(ChecklistDocument $document): mixed
    {
        try {
            $path = storage_path('app/private/'.$document->file_path);

            if (! file_exists($path)) {
                return response()->json(['message' => 'File not found'], 404);
            }

            return response()->download($path, $document->file_name, [
                'Content-Type' => $document->mime_type,
            ]);
        } catch (\Exception $e) {
            Log::error('Error downloading document', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Error downloading document'], 500);
        }
    }

    public function previewDocument(ChecklistDocument $document): JsonResponse
    {
        try {
            $path = storage_path('app/private/'.$document->file_path);

            if (! file_exists($path)) {
                return response()->json(['message' => 'File not found'], 404);
            }

            $url = Storage::disk('private')->url($document->file_path);

            return response()->json([
                'data' => [
                    'url' => $url,
                    'mime_type' => $document->mime_type,
                    'expires_at' => now()->addMinutes(5)->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating preview', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Error generating preview'], 500);
        }
    }

    public function deleteDocument(ChecklistDocument $document): JsonResponse
    {
        try {
            $item = $document->checklistItem;

            $this->checklistService->deleteDocumentFile($document);
            $document->delete();

            $item->update(['is_completed' => false]);

            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('Error deleting document', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Error deleting document'], 500);
        }
    }
}
