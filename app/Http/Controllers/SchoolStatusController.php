<?php

namespace App\Http\Controllers;

use App\Enums\FlpLevel;
use App\Models\Cluster;
use App\Models\User;
use App\Support\Monitoring;
use App\Support\Roles;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SchoolStatusController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $clusterNumber = $user->hasRole(Roles::DIVISION_ADMIN) ? $this->clusterNumber($request) : null;
        $search = trim($request->string('q')->toString());

        $schools = Monitoring::schools($user, $clusterNumber);
        $schools->load(['sections.learners']);

        $schools = $schools
            ->filter(function ($school) use ($search) {
                return $search === '' || str_contains(mb_strtolower($school->name), mb_strtolower($search));
            })
            ->map(fn ($school) => [
                ...$school->toMonitorArray(),
                'sections' => $school->sectionSummaries(),
            ])
            ->values();

        return Inertia::render('SchoolStatus', [
            'schools' => $schools,
            'levels' => FlpLevel::options(),
            'clusters' => $this->clusters($user),
            'filters' => [
                'cluster' => $clusterNumber ? (string) $clusterNumber : 'all',
                'q' => $search,
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
        if (! $user->hasRole(Roles::DIVISION_ADMIN)) {
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
