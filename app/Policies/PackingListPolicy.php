<?php

namespace App\Policies;

use App\Models\PackingList;
use App\Models\User;

class PackingListPolicy
{
    public function view(User $user, PackingList $packingList): bool
    {
        return $user->id == $packingList->user_id;
    }

    public function update(User $user, PackingList $packingList): bool
    {
        return $user->id == $packingList->user_id;
    }

    public function delete(User $user, PackingList $packingList): bool
    {
        return $user->id == $packingList->user_id;
    }
}
