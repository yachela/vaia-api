<?php

namespace App\Observers;

use App\Models\ChecklistItem;
use App\Models\TripDocumentChecklist;
use Illuminate\Support\Facades\Log;

class TripDocumentChecklistObserver
{
    public function created(TripDocumentChecklist $checklist): void
    {
        try {
            $position = 0;
            foreach (ChecklistItem::DEFAULT_CATEGORIES as $categoryName) {
                ChecklistItem::create([
                    'trip_document_checklist_id' => $checklist->id,
                    'name' => $categoryName,
                    'is_default' => true,
                    'is_completed' => false,
                    'position' => $position++,
                ]);
            }

            Log::info('Default checklist items created for trip', [
                'trip_id' => $checklist->trip_id,
                'checklist_id' => $checklist->id,
                'items_count' => $position,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create default checklist items', [
                'trip_id' => $checklist->trip_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function deleted(TripDocumentChecklist $checklist): void
    {
        Log::info('Trip document checklist deleted', [
            'checklist_id' => $checklist->id,
            'trip_id' => $checklist->trip_id,
        ]);
    }
}
