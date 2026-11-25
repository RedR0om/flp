<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['school_id', 'concern', 'action', 'responsible', 'status'])]
class TechnicalAssistance extends Model
{
    /**
     * @var list<string>
     */
    public const STATUSES = ['Requested', 'Ongoing', 'Resolved'];

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function cycle(): void
    {
        $index = array_search($this->status, self::STATUSES, true);
        $this->status = self::STATUSES[(($index === false ? 0 : $index) + 1) % count(self::STATUSES)];
        $this->save();
    }
}
