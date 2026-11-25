<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesMonitor;
use App\Models\School;
use App\Models\TechnicalAssistance;
use App\Support\Monitoring;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use ResolvesMonitor;

    public function index(Request $request): Response
    {
        $user = $this->monitorUser($request);
        $schools = Monitoring::schools($user);

        return Inertia::render('Reports', [
            'totals' => Monitoring::totals($schools),
            'schools' => $schools->map(fn (School $school) => $school->toMonitorArray())->values(),
            'actions' => TechnicalAssistance::query()
                ->whereIn('school_id', $schools->pluck('id'))
                ->with('school')
                ->get()
                ->map(fn (TechnicalAssistance $record) => [
                    'school' => $record->school->name,
                    'concern' => $record->concern,
                    'action' => $record->action,
                    'responsible' => $record->responsible,
                    'status' => $record->status,
                ])->values(),
        ]);
    }

    public function schools(Request $request): StreamedResponse
    {
        $user = $this->monitorUser($request);
        $schools = Monitoring::schools($user);

        return $this->csv('flp-school-status.csv', [
            ['School', 'Cluster', 'School Head', 'Learners', 'Learners Reached', 'Reach Rate (%)', 'FLP Level', 'Status', 'TA Needed'],
            ...$schools->map(fn (School $school) => $school->toMonitorArray())
                ->map(fn (array $row) => [
                    $row['name'],
                    $row['cluster_label'],
                    $row['head_name'],
                    $row['learners_total'],
                    $row['learners_reached'],
                    $row['reach_rate'],
                    $row['flp_level'],
                    $row['status_label'],
                    $row['ta_needed'] ? 'Yes' : 'No',
                ])->all(),
        ]);
    }

    public function actions(Request $request): StreamedResponse
    {
        $user = $this->monitorUser($request);
        $schools = $this->visibleSchools($user);
        $records = TechnicalAssistance::query()->whereIn('school_id', $schools->pluck('id'))->with('school')->get();

        return $this->csv('flp-action-tracker.csv', [
            ['School', 'Concern / Issue', 'Action Assigned', 'Responsible', 'Status'],
            ...$records->map(fn (TechnicalAssistance $record) => [
                $record->school->name,
                $record->concern,
                $record->action,
                $record->responsible,
                $record->status,
            ])->all(),
        ]);
    }

    /**
     * @param  list<list<mixed>>  $rows
     */
    private function csv(string $filename, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
