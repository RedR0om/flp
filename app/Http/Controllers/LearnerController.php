<?php

namespace App\Http\Controllers;

use App\Models\Learner;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class LearnerController extends Controller
{
    public function store(Request $request, Section $section): RedirectResponse
    {
        Gate::authorize('update', $section->school);

        $section->learners()->create(['net' => 'None', 'sim' => null]);

        return back();
    }

    public function update(Request $request, Learner $learner): RedirectResponse
    {
        Gate::authorize('update', $learner->section->school);

        $data = $request->validate([
            'net' => ['required', Rule::in(['Mobile Data', 'Home WiFi / Broadband', 'None'])],
            'sim' => ['nullable', Rule::in(['Globe', 'Smart', 'DITO'])],
        ]);

        if ($data['net'] !== 'Mobile Data') {
            $data['sim'] = null;
        }

        $learner->update($data);

        return back();
    }

    public function destroy(Learner $learner): RedirectResponse
    {
        Gate::authorize('update', $learner->section->school);
        $learner->delete();

        return back();
    }
}
