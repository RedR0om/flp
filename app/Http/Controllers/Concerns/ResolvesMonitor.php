<?php

namespace App\Http\Controllers\Concerns;

use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait ResolvesMonitor
{
    protected function monitorUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    /**
     * @return Collection<int, School>
     */
    protected function visibleSchools(User $user): Collection
    {
        return School::query()->visibleTo($user)->with('cluster')->orderBy('name')->get();
    }
}
