<?php

namespace App\Models;

use App\Enums\FlpLevel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['condition', 'level', 'is_active', 'starts_on'])]
class HazardEvent extends Model
{
    /**
     * @return HasMany<SchoolReport, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(SchoolReport::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => FlpLevel::class,
            'is_active' => 'boolean',
            'starts_on' => 'date',
        ];
    }
}
