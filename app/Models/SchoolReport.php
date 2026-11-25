<?php

namespace App\Models;

use App\Enums\FlpLevel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['school_id', 'hazard_event_id', 'learners_total', 'learners_reached', 'flp_level', 'ta_needed'])]
class SchoolReport extends Model
{
    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return BelongsTo<HazardEvent, $this>
     */
    public function hazardEvent(): BelongsTo
    {
        return $this->belongsTo(HazardEvent::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'learners_total' => 'integer',
            'learners_reached' => 'integer',
            'flp_level' => FlpLevel::class,
            'ta_needed' => 'boolean',
        ];
    }
}
