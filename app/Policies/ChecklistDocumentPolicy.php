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
        return $this->isTripParticipant($user, $trip);
    }

    public function view(User $user, TripDocumentChecklist $checklist): bool
    {
        return $this->isTripParticipant($user, $checklist->trip);
    }

    public function create(User $user, Trip $trip): bool
    {
        return $this->canEditTrip($user, $trip);
    }

    public function update(User $user, ChecklistItem $item): bool
    {
        return $this->canEditTrip($user, $item->checklist->trip);
    }

    public function delete(User $user, ChecklistItem $item): bool
    {
        return $this->canEditTrip($user, $item->checklist->trip);
    }

    public function uploadDocument(User $user, ChecklistItem $item): bool
    {
        return $this->canEditTrip($user, $item->checklist->trip);
    }

    public function downloadDocument(User $user, ChecklistDocument $document): bool
    {
        return $this->canViewTrip($user, $document->checklistItem->checklist->trip);
    }

    public function deleteDocument(User $user, ChecklistDocument $document): bool
    {
        return $this->canEditTrip($user, $document->checklistItem->checklist->trip);
    }

    private function isTripParticipant(User $user, Trip $trip): bool
    {
        if ((string) $user->id === (string) $trip->user_id) {
            return true;
        }

        return $trip->collaborators()->where('user_id', $user->id)->exists();
    }

    private function canEditTrip(User $user, Trip $trip): bool
    {
        if ((string) $user->id === (string) $trip->user_id) {
            return true;
        }

        return $trip->collaborators()
            ->where('user_id', $user->id)
            ->where('role', 'editor')
            ->exists();
    }

    private function canViewTrip(User $user, Trip $trip): bool
    {
        return $this->isTripParticipant($user, $trip);
    }
}
