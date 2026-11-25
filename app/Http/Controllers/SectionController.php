<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesMonitor;
use App\Models\Learner;
use App\Models\School;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SectionController extends Controller
{
    use ResolvesMonitor;

    public function index(Request $request): Response
    {
        $user = $this->monitorUser($request);
        $schools = $this->visibleSchools($user);
        $sections = Section::query()
            ->whereIn('school_id', $schools->pluck('id'))
            ->with(['school.cluster', 'learners'])
            ->orderBy('name')
            ->get();

        $selectedId = $request->integer('section');
        $selected = $sections->firstWhere('id', $selectedId) ?? $sections->first();

        return Inertia::render('Sections', [
            'sections' => $sections->map(fn (Section $section) => $this->summary($section))->values(),
            'selected' => $selected ? $this->detail($selected) : null,
            'schools' => $schools->map(fn (School $school) => [
                'id' => $school->id,
                'name' => $school->name,
                'cluster_label' => 'Cluster '.$school->cluster->number,
                'head_name' => $school->head_name,
                'supervisor_name' => $school->cluster->supervisor_name,
            ])->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->monitorUser($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'school_id' => ['required', 'integer', Rule::exists('schools', 'id')],
        ]);

        $school = School::query()->findOrFail($data['school_id']);
        Gate::authorize('update', $school);

        $section = $school->sections()->create(['name' => $data['name']]);

        return to_route('sections.index', ['section' => $section->id]);
    }

    public function destroy(Request $request, Section $section): RedirectResponse
    {
        $this->monitorUser($request);
        Gate::authorize('update', $section->school);
        $section->delete();

        return to_route('sections.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Section $section): array
    {
        $stats = $this->stats($section);

        return [
            'id' => $section->id,
            'name' => $section->name,
            'school_id' => $section->school_id,
            'school_name' => $section->school->name,
            'cluster_label' => 'Cluster '.$section->school->cluster->number,
            'head_name' => $section->school->head_name,
            'supervisor_name' => $section->school->cluster->supervisor_name,
            'learners' => $stats['total'],
            'with_access' => $stats['access'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(Section $section): array
    {
        return [
            ...$this->summary($section),
            'stats' => $this->stats($section),
            'learners' => $section->learners->map(fn (Learner $learner) => [
                'id' => $learner->id,
                'net' => $learner->net,
                'sim' => $learner->sim,
            ])->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function stats(Section $section): array
    {
        $learners = $section->learners;
        $total = $learners->count();
        $none = $learners->where('net', 'None')->count();
        $mobile = $learners->where('net', 'Mobile Data');

        $segment = function (string $name, int $count, string $color) {
            return ['key' => $name, 'label' => $name, 'count' => $count, 'color' => $color];
        };

        return [
            'total' => $total,
            'access' => $total === 0 ? 0 : (int) round(($total - $none) / $total * 100),
            'no_access' => $total === 0 ? 0 : (int) round($none / $total * 100),
            'internet' => [
                $segment('Mobile Data', $learners->where('net', 'Mobile Data')->count(), '#1f78d1'),
                $segment('Home WiFi / Broadband', $learners->where('net', 'Home WiFi / Broadband')->count(), '#1f9d55'),
                $segment('None', $none, '#e0413a'),
            ],
            'sim' => [
                $segment('Globe', $mobile->where('sim', 'Globe')->count(), '#1f9d55'),
                $segment('Smart', $mobile->where('sim', 'Smart')->count(), '#1f78d1'),
                $segment('DITO', $mobile->where('sim', 'DITO')->count(), '#ee9422'),
            ],
        ];
    }
}
