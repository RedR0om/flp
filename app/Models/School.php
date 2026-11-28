<?php

namespace App\Models;

use App\Enums\FlpLevel;
use App\Support\Reach;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['cluster_id', 'name', 'head_name'])]
class School extends Model
{
    /**
     * @return BelongsTo<Cluster, $this>
     */
    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }

    /**
     * @return HasOne<SchoolReport, $this>
     */
    public function report(): HasOne
    {
        return $this->hasOne(SchoolReport::class);
    }

    /**
     * @return HasMany<Section, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    /**
     * @return HasMany<Validation, $this>
     */
    public function validations(): HasMany
    {
        return $this->hasMany(Validation::class);
    }

    /**
     * @return HasMany<TechnicalAssistance, $this>
     */
    public function technicalAssistances(): HasMany
    {
        return $this->hasMany(TechnicalAssistance::class);
    }

    /**
     * @param  Builder<School>  $query
     * @return Builder<School>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->seesEverySchool()) {
            return $query;
        }

        if ($user->hasAnyRole(Roles::CLUSTER_EDITORS)) {
            return $query->where('cluster_id', $user->cluster_id ?? 0);
        }

        return $query->whereKey(0);
    }

    /**
     * @return array<string, mixed>
     */
    public function toMonitorArray(): array
    {
        $report = $this->report;
        $rate = Reach::rate($report?->learners_total, $report?->learners_reached);
        $band = Reach::band($rate);
        $level = $report?->flp_level ?? FlpLevel::Hinay;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'head_name' => $this->head_name,
            'cluster_id' => $this->cluster_id,
            'cluster_number' => $this->cluster->number,
            'cluster_label' => 'Cluster '.$this->cluster->number,
            'supervisor_name' => $this->cluster->supervisor_name,
            'supervisor_title' => $this->cluster->supervisor_title,
            'learners_total' => $report?->learners_total,
            'learners_reached' => $report?->learners_reached,
            'flp_level' => $level->value,
            'flp_label' => $level->label(),
            'ta_needed' => (bool) $report?->ta_needed,
            'reach_rate' => $rate,
            'status' => $band,
            'status_label' => Reach::label($band),
        ];
    }

    /**
     * Section learner counts for the school detail view.
     * Internet access is the share of learners whose connection is not "None".
     *
     * @return list<array{id: int, name: string, learners: int, internet_percent: int}>
     */
    public function sectionSummaries(): array
    {
        return $this->sections
            ->sortBy('name')
            ->map(function (Section $section): array {
                $learners = $section->learners;
                $count = $learners->count();
                $withInternet = $learners->where('net', '!=', 'None')->count();

                return [
                    'id' => $section->id,
                    'name' => $section->name,
                    'learners' => $count,
                    'internet_percent' => $count > 0 ? (int) round($withInternet / $count * 100) : 0,
                ];
            })
            ->values()
            ->all();
    }
}
