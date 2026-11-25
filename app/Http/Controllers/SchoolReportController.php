<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSchoolReportRequest;
use App\Models\HazardEvent;
use App\Models\School;
use App\Support\DivisionSnapshot;
use Illuminate\Http\RedirectResponse;

class SchoolReportController extends Controller
{
    public function update(UpdateSchoolReportRequest $request, School $school): RedirectResponse
    {
        $school->report()->updateOrCreate([], [
            ...$request->safe()->only(['learners_total', 'learners_reached', 'flp_level', 'ta_needed']),
            'hazard_event_id' => $school->report?->hazard_event_id
                ?? HazardEvent::query()->where('is_active', true)->value('id'),
        ]);

        DivisionSnapshot::record();

        return back();
    }
}
