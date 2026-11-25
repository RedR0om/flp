<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesMonitor;
use App\Models\School;
use App\Models\Validation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ValidationController extends Controller
{
    use ResolvesMonitor;

    public function index(Request $request): Response
    {
        $user = $this->monitorUser($request);
        $schools = $this->visibleSchools($user);
        $records = Validation::query()
            ->whereIn('school_id', $schools->pluck('id'))
            ->with('school')
            ->orderBy('school_id')
            ->get();

        $ok = $records->where('severity', 'ok')->count();
        $minor = $records->where('severity', 'minor')->count();
        $major = $records->where('severity', 'major')->count();

        return Inertia::render('Validations', [
            'records' => $records->map(fn (Validation $record) => [
                'id' => $record->id,
                'school' => $record->school->name,
                'finding' => $record->finding,
                'severity' => $record->severity,
            ])->values(),
            'segments' => [
                ['key' => 'ok', 'label' => 'Fully Validated', 'count' => $ok, 'color' => '#1f9d55'],
                ['key' => 'minor', 'label' => 'With Minor Concerns', 'count' => $minor, 'color' => '#ee9422'],
                ['key' => 'major', 'label' => 'With Significant Concerns', 'count' => $major, 'color' => '#e0413a'],
            ],
            'schools' => $schools->map(fn (School $school) => ['id' => $school->id, 'name' => $school->name])->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->monitorUser($request);
        $data = $request->validate([
            'school_id' => ['required', 'integer', Rule::exists('schools', 'id')],
            'finding' => ['required', 'string', 'max:255'],
            'severity' => ['required', Rule::in(['ok', 'minor', 'major'])],
        ]);

        $school = School::query()->findOrFail($data['school_id']);
        Gate::authorize('update', $school);
        $school->validations()->create($data);

        return back();
    }

    public function destroy(Request $request, Validation $validation): RedirectResponse
    {
        $this->monitorUser($request);
        Gate::authorize('update', $validation->school);
        $validation->delete();

        return back();
    }
}
