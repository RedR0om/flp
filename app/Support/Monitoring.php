<?php

namespace App\Support;

use App\Models\HazardEvent;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Collection;

final class Monitoring
{
    /**
     * @return Collection<int, School>
     */
    public static function schools(User $user, ?int $clusterNumber = null): Collection
    {
        $schools = School::query()
            ->visibleTo($user)
            ->with(['cluster', 'report'])
            ->orderBy('name')
            ->get();

        if ($clusterNumber === null) {
            return $schools;
        }

        return $schools
            ->filter(fn (School $school) => $school->cluster->number === $clusterNumber)
            ->values();
    }

    /**
     * @param  Collection<int, School>  $schools
     * @return array{schools: int, learners: int, reached: int, rate: float, issues: int, ta: int}
     */
    public static function totals(Collection $schools): array
    {
        $learners = 0;
        $reached = 0;
        $issues = 0;
        $ta = 0;

        foreach ($schools as $school) {
            $report = $school->report;
            $rate = Reach::rate($report?->learners_total, $report?->learners_reached);

            if ($report?->learners_total !== null && $report->learners_reached !== null) {
                $learners += $report->learners_total;
                $reached += $report->learners_reached;
            }

            if (Reach::isIssue(Reach::band($rate))) {
                $issues++;
            }

            if ($report?->ta_needed) {
                $ta++;
            }
        }

        return [
            'schools' => $schools->count(),
            'learners' => $learners,
            'reached' => $reached,
            'rate' => Reach::rate($learners, $reached) ?? 0.0,
            'issues' => $issues,
            'ta' => $ta,
        ];
    }

    /**
     * Enrollment is the sum of submitted learner counts, grouped by cluster.
     * A school with no learner count is counted as a school and left out of the sum.
     *
     * @param  Collection<int, School>  $schools
     * @return array{total: int, with_records: int, schools: int, rows: list<array{cluster: int, label: string, schools: int, enrollment: int, with_enrollment: int, share: float}>}
     */
    public static function enrollment(Collection $schools): array
    {
        /** @var array<int, array{cluster: int, label: string, schools: int, enrollment: int, with_enrollment: int}> $groups */
        $groups = [];

        foreach ($schools as $school) {
            $number = $school->cluster->number;
            $groups[$number] ??= [
                'cluster' => $number,
                'label' => 'Cluster '.$number,
                'schools' => 0,
                'enrollment' => 0,
                'with_enrollment' => 0,
            ];

            $groups[$number]['schools']++;

            if ($school->report?->learners_total !== null) {
                $groups[$number]['enrollment'] += $school->report->learners_total;
                $groups[$number]['with_enrollment']++;
            }
        }

        ksort($groups);

        $rows = array_values($groups);
        $total = array_sum(array_column($rows, 'enrollment'));

        foreach ($rows as $index => $row) {
            $rows[$index]['share'] = $total > 0 ? round($row['enrollment'] / $total * 100, 1) : 0.0;
        }

        return [
            'total' => $total,
            'with_records' => array_sum(array_column($rows, 'with_enrollment')),
            'schools' => $schools->count(),
            'rows' => $rows,
        ];
    }

    /**
     * @param  Collection<int, School>  $schools
     * @return list<array{key: string, label: string, count: int, color: string}>
     */
    public static function segments(Collection $schools): array
    {
        $counts = [
            'ex' => 0,
            'ok' => 0,
            'na' => 0,
            'hp' => 0,
            'cr' => 0,
            'none' => 0,
        ];

        foreach ($schools as $school) {
            $report = $school->report;
            $band = Reach::band(Reach::rate($report?->learners_total, $report?->learners_reached));
            $counts[$band]++;
        }

        $colors = [
            'ex' => '#178a4a',
            'ok' => '#7ccf97',
            'na' => '#f2c230',
            'hp' => '#ee9422',
            'cr' => '#e0413a',
            'none' => '#c5cdd6',
        ];

        return array_map(fn (string $key) => [
            'key' => $key,
            'label' => Reach::label($key),
            'count' => $counts[$key],
            'color' => $colors[$key],
        ], array_keys($counts));
    }

    /**
     * @param  Collection<int, School>  $schools
     * @return list<array<string, mixed>>
     */
    public static function priorities(Collection $schools): array
    {
        return $schools
            ->filter(fn (School $school) => (bool) $school->report?->ta_needed)
            ->sortBy(function (School $school) {
                $rate = Reach::rate($school->report?->learners_total, $school->report?->learners_reached);

                return $rate ?? 1000;
            })
            ->take(8)
            ->map(fn (School $school) => $school->toMonitorArray())
            ->values()
            ->all();
    }

    /**
     * @return array{condition: string, level: string, label: string}|null
     */
    public static function activeHazard(): ?array
    {
        $event = HazardEvent::query()->where('is_active', true)->latest('id')->first();

        if ($event === null) {
            return null;
        }

        return [
            'condition' => $event->condition,
            'level' => $event->level->value,
            'label' => $event->level->label(),
        ];
    }
}
