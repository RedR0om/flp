<?php

namespace App\Http\Controllers;

use App\Enums\FlpLevel;
use App\Http\Controllers\Concerns\ResolvesMonitor;
use App\Models\HazardEvent;
use App\Support\DivisionSnapshot;
use App\Support\Hazards;
use App\Support\Monitoring;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HazardController extends Controller
{
    use ResolvesMonitor;

    public function index(Request $request): Response
    {
        $user = $this->monitorUser($request);
        $event = HazardEvent::query()->where('is_active', true)->first();
        $current = $event ? Hazards::find($event->condition) : null;

        return Inertia::render('Hazards', [
            'hazards' => Hazards::all(),
            'current' => $current ?? ($event ? [
                'condition' => $event->condition,
                'level' => $event->level->value,
                'label' => $event->level->label(),
                'description' => $event->level->description(),
            ] : Hazards::all()[1]),
            'schools' => Monitoring::schools($user)->map(fn ($school) => $school->toMonitorArray())->values(),
            'can_set_division' => $user->hasRole(Roles::DIVISION_ADMIN),
            'levels' => FlpLevel::options(),
        ]);
    }

    public function select(Request $request): RedirectResponse
    {
        $user = $this->monitorUser($request);
        abort_unless($user->hasRole(Roles::DIVISION_ADMIN), 403);

        $data = $request->validate(['condition' => ['required', 'string']]);
        $hazard = Hazards::find($data['condition']);
        abort_unless($hazard !== null, 422);

        HazardEvent::query()->where('is_active', true)->update([
            'condition' => $hazard['condition'],
            'level' => $hazard['level'],
        ]);

        return back();
    }

    public function apply(Request $request): RedirectResponse
    {
        $user = $this->monitorUser($request);

        if ($user->hasRole(Roles::DIVISION_ADMIN) && $request->filled('condition')) {
            $hazard = Hazards::find($request->string('condition')->toString());
            abort_unless($hazard !== null, 422);

            HazardEvent::query()->where('is_active', true)->update([
                'condition' => $hazard['condition'],
                'level' => $hazard['level'],
            ]);
        }

        $event = HazardEvent::query()->where('is_active', true)->firstOrFail();

        foreach (Monitoring::schools($user) as $school) {
            $report = $school->report;
            $school->report()->updateOrCreate([], [
                'hazard_event_id' => $event->id,
                'flp_level' => $event->level,
                'learners_total' => $report?->learners_total,
                'learners_reached' => $report?->learners_reached,
                'ta_needed' => (bool) $report?->ta_needed,
            ]);
        }

        DivisionSnapshot::record();

        return back();
    }
}
