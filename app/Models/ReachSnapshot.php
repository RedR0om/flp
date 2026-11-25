<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['recorded_on', 'reach_rate', 'learners', 'reached'])]
class ReachSnapshot extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recorded_on' => 'date',
            'reach_rate' => 'float',
            'learners' => 'integer',
            'reached' => 'integer',
        ];
    }
}
