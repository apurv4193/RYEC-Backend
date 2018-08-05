<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class Policy
{
    use HandlesAuthorization;

    //sample
    public function isSuperAdmin(User $user)
    {
        return ($user->role->slug == 'superadmin');
    }
}
