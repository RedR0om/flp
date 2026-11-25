<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesMonitor;
use App\Models\ModalityCount;
use App\Models\School;
use App\Support\Modalities;
use App\Support\Reach;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ModalityController extends Controller
{
    use ResolvesMonitor;

    public function index(Request $request): Response
    {
        $user = $this->monitorUser($request);
        $schools = $this->visibleSchools($user);
        $counts = ModalityCount::query()->whereIn('school_id', $schools->pluck('id'))->get();

        $rows = collect(Modalities::ALL)->map(function (string $modality) use ($counts) {
            $matched = $counts->where('modality', $modality);
            $reached = (int) $matched->sum('learners_reached');
            $total = (int) $matched->sum('learners_total');
            $rate = Reach::rate($total, $reached);

            return [
                'modality' => $modality,
                'learners_reached' => $reached,
                'learners_total' => $total,
                'reach_rate' => $rate,
                'status' => $band = Reach::band($total === 0 ? null : $rate),
                'status_label' => Reach::label($band),
            ];
        });

        $reached = (int) $rows->sum('learners_reached');
        $total = (int) $rows->sum('learners_total');

        return Inertia::render('Modalities', [
            'rows' => $rows->values(),
            'totals' => [
                'learners_reached' => $reached,
                'learners_total' => $total,
                'reach_rate' => Reach::rate($total, $reached),
            ],
            'schools' => $schools->map(fn (School $school) => [
                'id' => $school->id,
                'name' => $school->name,
                'counts' => collect(Modalities::ALL)->map(function (string $modality) use ($counts, $school) {
                    $row = $counts->first(fn (ModalityCount $count) => $count->school_id === $school->id && $count->modality === $modality);

                    return [
                        'modality' => $modality,
                        'learners_total' => $row?->learners_total ?? 0,
                        'learners_reached' => $row?->learners_reached ?? 0,
                    ];
                })->values(),
            ])->values(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->monitorUser($request);
        $data = $request->validate([
            'school_id' => ['required', 'integer', Rule::exists('schools', 'id')],
            'modality' => ['required', Rule::in(Modalities::ALL)],
            'learners_total' => ['required', 'integer', 'min:0'],
            'learners_reached' => ['required', 'integer', 'min:0', 'lte:learners_total'],
        ]);

        $school = School::query()->findOrFail($data['school_id']);
        Gate::authorize('update', $school);

        ModalityCount::query()->updateOrCreate(
            ['school_id' => $school->id, 'modality' => $data['modality']],
            ['learners_total' => $data['learners_total'], 'learners_reached' => $data['learners_reached']],
        );

        return back();
    }
}
