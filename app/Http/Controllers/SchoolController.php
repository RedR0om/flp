<?php

namespace App\Http\Controllers;

use App\Enums\FlpLevel;
use App\Http\Requests\StoreSchoolRequest;
use App\Models\HazardEvent;
use App\Models\School;
use App\Models\User;
use App\Support\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SchoolController extends Controller
{
    public function store(StoreSchoolRequest $request): RedirectResponse
    {
        $school = School::query()->create([
            'name' => $request->string('name')->trim()->toString(),
            'cluster_id' => $request->integer('cluster_id'),
            'head_name' => $request->filled('head_name') ? $request->string('head_name')->trim()->toString() : null,
        ]);

        $school->report()->create([
            'hazard_event_id' => HazardEvent::query()->where('is_active', true)->value('id'),
            'flp_level' => FlpLevel::Hinay,
            'ta_needed' => false,
        ]);

        ActivityLog::record(
            $request->user() instanceof User ? $request->user() : null,
            $school->id,
            null,
            'School added',
            $school->name,
        );

        return back();
    }

    public function destroy(Request $request, School $school): RedirectResponse
    {
        Gate::authorize('delete', $school);

        ActivityLog::record(
            $request->user() instanceof User ? $request->user() : null,
            null,
            $school->cluster_id,
            'School removed',
            $school->name,
        );

        $school->delete();

        return back();
    }
}
