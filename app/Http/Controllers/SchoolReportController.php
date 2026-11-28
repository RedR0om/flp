<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSchoolReportRequest;
use App\Models\HazardEvent;
use App\Models\School;
use App\Models\User;
use App\Support\ActivityLog;
use App\Support\DivisionSnapshot;
use Illuminate\Http\RedirectResponse;

class SchoolReportController extends Controller
{
    public function update(UpdateSchoolReportRequest $request, School $school): RedirectResponse
    {
        $report = $school->report()->updateOrCreate([], [
            ...$request->safe()->only(['learners_total', 'learners_reached', 'flp_level', 'ta_needed']),
            'hazard_event_id' => $school->report?->hazard_event_id
                ?? HazardEvent::query()->where('is_active', true)->value('id'),
        ]);

        $counts = $report->learners_total !== null && $report->learners_reached !== null
            ? $report->learners_reached.' of '.$report->learners_total.' learners reached'
            : 'no learner count yet';

        ActivityLog::record(
            $request->user() instanceof User ? $request->user() : null,
            $school->id,
            null,
            'School report updated',
            $report->flp_level->value.' · '.$counts,
        );

        DivisionSnapshot::record();

        return back();
    }
}
