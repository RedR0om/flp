<?php

namespace App\Http\Controllers;

use App\Enums\FlpLevel;
use App\Http\Requests\StoreSchoolRequest;
use App\Models\HazardEvent;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
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

        return back();
    }

    public function destroy(School $school): RedirectResponse
    {
        Gate::authorize('delete', $school);

        $school->delete();

        return back();
    }
}
