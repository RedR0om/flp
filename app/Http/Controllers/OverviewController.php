<?php

namespace App\Http\Controllers;

use App\Enums\FlpLevel;
use App\Models\Cluster;
use App\Models\ReachSnapshot;
use App\Models\User;
use App\Support\Monitoring;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OverviewController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $clusterNumber = $user->seesEverySchool() ? $this->clusterNumber($request) : null;
        $schools = Monitoring::schools($user, $clusterNumber);
        $search = trim($request->string('q')->toString());

        $snapshot = $schools
            ->filter(function ($school) use ($search) {
                return $search === '' || str_contains(mb_strtolower($school->name), mb_strtolower($search));
            })
            ->map(fn ($school) => $school->toMonitorArray())
            ->values();

        $updatedAt = $schools->pluck('report.updated_at')->filter()->max();
        $submissionsToday = $schools->filter(fn ($school) => $school->report?->updated_at?->isToday())->count();

        return Inertia::render('Overview', [
            'totals' => Monitoring::totals($schools),
            'enrollment' => Monitoring::enrollment($schools),
            'segments' => Monitoring::segments($schools),
            'schools' => $schools->map(fn ($school) => $school->toMonitorArray())->values(),
            'snapshot' => $snapshot,
            'priorities' => Monitoring::priorities($schools),
            'hazard' => Monitoring::activeHazard(),
            'levels' => FlpLevel::options(),
            'clusters' => $this->clusters($user),
            'filters' => [
                'cluster' => $clusterNumber ? (string) $clusterNumber : 'all',
                'q' => $search,
            ],
            'meta' => [
                'updated_at' => $updatedAt?->timezone(config('app.timezone'))->format('M j, Y · g:i A'),
                'submissions_today' => $submissionsToday,
            ],
            'trend' => ReachSnapshot::query()
                ->orderBy('recorded_on')
                ->get()
                ->map(fn (ReachSnapshot $snapshot) => [
                    'date' => $snapshot->recorded_on->format('M j'),
                    'rate' => $snapshot->reach_rate,
                ])
                ->values(),
            'steps' => [
                ['title' => 'Report', 'detail' => 'School/FLP Focal submits FLP status.', 'color' => '#1f78d1'],
                ['title' => 'Review', 'detail' => 'FLP Focal checks completeness and validates.', 'color' => '#0a5c9e'],
                ['title' => 'Reach & Validate', 'detail' => 'Supervisors monitor via home visitation.', 'color' => '#e8683f'],
                ['title' => 'Respond', 'detail' => 'Issues assigned for TA and intervention.', 'color' => '#7b57c8'],
                ['title' => 'Resolve', 'detail' => 'School implements; supervisor validates closure.', 'color' => '#1f9d55'],
            ],
        ]);
    }

    private function clusterNumber(Request $request): ?int
    {
        $number = $request->integer('cluster');

        if ($number < 1) {
            return null;
        }

        return Cluster::query()->where('number', $number)->exists() ? $number : null;
    }

    /**
     * @return list<array{id: int, number: int, supervisor_name: string, supervisor_title: string}>
     */
    private function clusters(User $user): array
    {
        if (! $user->seesEverySchool()) {
            return [];
        }

        return Cluster::query()
            ->orderBy('number')
            ->get(['id', 'number', 'supervisor_name', 'supervisor_title'])
            ->map(fn (Cluster $cluster) => [
                'id' => $cluster->id,
                'number' => $cluster->number,
                'supervisor_name' => $cluster->supervisor_name,
                'supervisor_title' => $cluster->supervisor_title,
            ])
            ->all();
    }
}
