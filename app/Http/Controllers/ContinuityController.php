<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesMonitor;
use App\Models\Activity;
use App\Models\School;
use App\Models\User;
use App\Support\Monitoring;
use App\Support\Reach;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ContinuityController extends Controller
{
    use ResolvesMonitor;

    public function events(Request $request): Response
    {
        $schools = $this->schools($request);

        return Inertia::render('FlpEvents', [
            'events' => $schools->map(function ($school) {
                $row = $school->toMonitorArray();
                $hazard = $school->report?->hazardEvent;

                return [
                    'id' => $row['id'],
                    'school' => $row['name'],
                    'cluster' => $row['cluster_label'],
                    'scenario' => $hazard?->condition ?? 'No condition recorded',
                    'status' => $row['flp_label'],
                    'level' => $row['flp_level'],
                    'period' => $hazard?->starts_on?->format('Y-m-d') ?? $school->report?->updated_at?->format('Y-m-d'),
                    'learners_total' => $row['learners_total'],
                    'learners_reached' => $row['learners_reached'],
                    'reach_rate' => $row['reach_rate'],
                    'reach_label' => $row['status_label'],
                ];
            })->values(),
        ]);
    }

    public function masterData(Request $request): Response
    {
        $schools = $this->schools($request);

        return Inertia::render('MasterData', [
            'schools' => $schools->map(fn ($school) => [
                'id' => $school->id,
                'name' => $school->name,
                'head_name' => $school->head_name,
                'cluster' => 'Cluster '.$school->cluster->number,
                'supervisor' => $school->cluster->supervisor_name,
                'supervisor_title' => $school->cluster->supervisor_title,
            ])->values(),
        ]);
    }

    public function learnerReach(Request $request): Response
    {
        $schools = $this->schools($request);

        return Inertia::render('LearnerReach', [
            'totals' => Monitoring::totals($schools),
            'schools' => $schools->map(fn ($school) => $school->toMonitorArray())->values(),
        ]);
    }

    public function issues(Request $request): Response
    {
        $schools = $this->schools($request)
            ->filter(function ($school) {
                $rate = Reach::rate($school->report?->learners_total, $school->report?->learners_reached);

                return Reach::isIssue(Reach::band($rate)) || (bool) $school->report?->ta_needed;
            })
            ->values();

        return Inertia::render('Issues', [
            'schools' => $schools->map(fn ($school) => $school->toMonitorArray())->values(),
        ]);
    }

    public function history(Request $request): Response
    {
        $user = $this->monitorUser($request);
        $activities = $this->visibleActivities($user);
        $readIds = $user->activitiesRead()->pluck('activities.id');

        return Inertia::render('ActivityHistory', [
            'activities' => $activities->map(fn (Activity $activity) => [
                'id' => $activity->id,
                'occurred_at' => $activity->occurred_at->timezone(config('app.timezone'))->format('Y-m-d H:i'),
                'summary' => $activity->summary,
                'detail' => $activity->detail,
                'school' => $activity->school?->name,
                'actor' => $activity->user?->name,
                'read' => $readIds->contains($activity->id),
            ])->values(),
            'unread' => $activities->reject(fn (Activity $activity) => $readIds->contains($activity->id))->count(),
        ]);
    }

    public function read(Request $request, Activity $activity): RedirectResponse
    {
        $user = $this->monitorUser($request);
        abort_unless($this->visibleActivities($user)->contains('id', $activity->id), 403);

        $activity->readers()->syncWithoutDetaching([
            $user->id => ['read_at' => now()],
        ]);

        return back();
    }

    /**
     * @return Collection<int, School>
     */
    private function schools(Request $request): Collection
    {
        $user = $this->monitorUser($request);

        return Monitoring::schools($user)->load('report.hazardEvent');
    }

    /**
     * @return Collection<int, Activity>
     */
    private function visibleActivities(User $user): Collection
    {
        $schoolIds = Monitoring::schools($user)->pluck('id');

        return Activity::query()
            ->with(['school', 'user'])
            ->where(function ($query) use ($user, $schoolIds) {
                $query->whereIn('school_id', $schoolIds);
                $query->orWhere(fn ($division) => $division->whereNull('school_id')->whereNull('cluster_id'));

                if ($user->seesEverySchool()) {
                    $query->orWhereNotNull('cluster_id');
                } elseif ($user->hasAnyRole(Roles::CLUSTER_EDITORS) && $user->cluster_id) {
                    $query->orWhere('cluster_id', $user->cluster_id);
                }
            })
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->get();
    }
}
