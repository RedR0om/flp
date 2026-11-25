<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesMonitor;
use App\Models\School;
use App\Models\TechnicalAssistance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TechnicalAssistanceController extends Controller
{
    use ResolvesMonitor;

    public function index(Request $request): Response
    {
        $user = $this->monitorUser($request);
        $schools = $this->visibleSchools($user);
        $records = TechnicalAssistance::query()
            ->whereIn('school_id', $schools->pluck('id'))
            ->with('school')
            ->orderBy('id')
            ->get();

        return Inertia::render('Actions', [
            'records' => $records->map(fn (TechnicalAssistance $record) => [
                'id' => $record->id,
                'school' => $record->school->name,
                'concern' => $record->concern,
                'action' => $record->action,
                'responsible' => $record->responsible,
                'status' => $record->status,
            ])->values(),
            'schools' => $schools->map(fn (School $school) => ['id' => $school->id, 'name' => $school->name])->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->monitorUser($request);
        $data = $request->validate([
            'school_id' => ['required', 'integer', Rule::exists('schools', 'id')],
            'concern' => ['required', 'string', 'max:255'],
            'action' => ['required', 'string', 'max:1000'],
            'responsible' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(TechnicalAssistance::STATUSES)],
        ]);

        $school = School::query()->findOrFail($data['school_id']);
        Gate::authorize('update', $school);
        $school->technicalAssistances()->create($data);

        return back();
    }

    public function update(Request $request, TechnicalAssistance $technicalAssistance): RedirectResponse
    {
        $this->monitorUser($request);
        Gate::authorize('update', $technicalAssistance->school);
        $technicalAssistance->cycle();

        return back();
    }

    public function destroy(Request $request, TechnicalAssistance $technicalAssistance): RedirectResponse
    {
        $this->monitorUser($request);
        Gate::authorize('update', $technicalAssistance->school);
        $technicalAssistance->delete();

        return back();
    }
}
