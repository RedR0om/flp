<?php

namespace App\Support;

use App\Models\ReachSnapshot;
use App\Models\School;

final class DivisionSnapshot
{
    public static function record(): void
    {
        $totals = Monitoring::totals(School::query()->with('report')->get());

        ReachSnapshot::query()->updateOrCreate(
            ['recorded_on' => now()->toDateString()],
            [
                'reach_rate' => $totals['rate'],
                'learners' => $totals['learners'],
                'reached' => $totals['reached'],
            ],
        );
    }
}
