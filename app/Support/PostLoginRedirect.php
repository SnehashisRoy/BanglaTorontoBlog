<?php

namespace App\Support;

use App\Models\User;

class PostLoginRedirect
{
    /**
     * Where a user should land right after authenticating, based on their role.
     */
    public static function for(?User $user): string
    {
        return match (true) {
            $user?->is_admin => route('admin.vendors.index'),
            $user?->vendor !== null => route('vendor.dashboard'),
            default => route('dashboard'),
        };
    }
}
