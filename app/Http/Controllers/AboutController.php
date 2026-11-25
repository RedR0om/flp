<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesMonitor;
use App\Models\Cluster;
use App\Models\School;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AboutController extends Controller
{
    use ResolvesMonitor;

    public function __invoke(Request $request): Response
    {
        $this->monitorUser($request);

        return Inertia::render('About', [
            'schools' => School::query()->count(),
            'clusters' => Cluster::query()->count(),
        ]);
    }
}
