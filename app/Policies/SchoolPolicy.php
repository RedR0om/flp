<?php

namespace App\Policies;

use App\Models\School;
use App\Models\User;
use App\Support\Roles;

class SchoolPolicy
{
    public function view(User $user, School $school): bool
    {
        return $user->canViewSchool($school);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Roles::DIVISION_ADMIN);
    }

    public function update(User $user, School $school): bool
    {
        return $user->canEditReport($school);
    }

    public function delete(User $user, School $school): bool
    {
        return $user->hasRole(Roles::DIVISION_ADMIN) && $user->canViewSchool($school);
    }
}
