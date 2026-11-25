<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['school_id', 'modality', 'learners_total', 'learners_reached'])]
class ModalityCount extends Model
{
    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'learners_total' => 'integer',
            'learners_reached' => 'integer',
        ];
    }
}
