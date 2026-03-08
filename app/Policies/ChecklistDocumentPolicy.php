<?php

namespace App\Policies;

use App\Models\ChecklistDocument;
use App\Models\ChecklistItem;
use App\Models\Trip;
use App\Models\TripDocumentChecklist;
use App\Models\User;

class ChecklistDocumentPolicy
{
    public function viewAny(User $user, Trip $trip): bool
    {
        return $this->ownsTrip($user, $trip);
    }

    public function view(User $user, TripDocumentChecklist $checklist): bool
    {
        return $this->ownsTrip($user, $checklist->trip);
    }

    public function create(User $user, Trip $trip): bool
    {
        return $this->ownsTrip($user, $trip);
    }

    public function update(User $user, ChecklistItem $item): bool
    {
        return $this->ownsTrip($user, $item->checklist->trip);
    }

    public function delete(User $user, ChecklistItem $item): bool
    {
        return $this->ownsTrip($user, $item->checklist->trip);
    }

    public function uploadDocument(User $user, ChecklistItem $item): bool
    {
        return $this->ownsTrip($user, $item->checklist->trip);
    }

    public function downloadDocument(User $user, ChecklistDocument $document): bool
    {
        return $this->ownsTrip($user, $document->checklistItem->checklist->trip);
    }

    public function deleteDocument(User $user, ChecklistDocument $document): bool
    {
        return $this->ownsTrip($user, $document->checklistItem->checklist->trip);
    }

    private function ownsTrip(User $user, Trip $trip): bool
    {
        return (string) $user->id === (string) $trip->user_id;
    }
}
