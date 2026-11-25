<?php

namespace App\Support;

use App\Enums\FlpLevel;

final class Hazards
{
    /**
     * @return list<array{condition: string, level: string, note: string|null}>
     */
    public static function all(): array
    {
        $rows = [
            ['Normal Conditions', FlpLevel::Hayo, null],
            ['Weather – Yellow Heavy Rainfall Warning', FlpLevel::Hinay, null],
            ['Weather – Orange Heavy Rainfall Warning', FlpLevel::Hinga, null],
            ['Weather – Red Heavy Rainfall Warning', FlpLevel::Hinto, null],
            ['Weather – Tropical Cyclone (TCWS Signal No. 1)', FlpLevel::Hinay, 'Suggested level – confirm against DO 014.'],
            ['Weather – Tropical Cyclone (TCWS Signal No. 2 or higher)', FlpLevel::Hinto, null],
            ['Weather – Flooding / Inundation', FlpLevel::Hinto, 'Suggested level – confirm against DO 014.'],
            ['Weather – Extreme Heat (low-level heat advisory)', FlpLevel::Hinay, null],
            ['Weather – Extreme Heat (high heat index)', FlpLevel::Hinga, null],
            ['Geological – Earthquake (post-earthquake recovery)', FlpLevel::Hinga, null],
            ['Geological – Earthquake (structural damage)', FlpLevel::Hinto, null],
            ['Geological – Volcanic Eruption (ashfall / vog)', FlpLevel::Hinto, 'Suggested level – confirm against DO 014.'],
            ['Geological – Landslide / Mudslide', FlpLevel::Hinto, 'Suggested level – confirm against DO 014.'],
            ['Health – Epidemic / Pandemic / Public Health Emergency', FlpLevel::Hinga, 'Suggested level – confirm against DO 014.'],
            ['Environmental / Toxic Hazard (chemical or gas leak, severe air pollution)', FlpLevel::Hinto, 'Suggested level – confirm against DO 014.'],
            ['Security – Armed Conflict / Civil Unrest', FlpLevel::Hinto, null],
            ['Security – Violence / Threats (bomb threat, violent protest)', FlpLevel::Hinto, 'Suggested level – confirm against DO 014.'],
            ['Infrastructure – Fire / Building Structural Failure', FlpLevel::Hinto, null],
            ['Infrastructure – Power / Utility Disruption', FlpLevel::Hinay, 'Suggested level – confirm against DO 014.'],
        ];

        return array_map(fn (array $row) => [
            'condition' => $row[0],
            'level' => $row[1]->value,
            'label' => $row[1]->label(),
            'description' => $row[1]->description().($row[2] ? ' '.$row[2] : ''),
        ], $rows);
    }

    /**
     * @return array{condition: string, level: string, label: string, description: string}|null
     */
    public static function find(string $condition): ?array
    {
        foreach (self::all() as $hazard) {
            if ($hazard['condition'] === $condition) {
                return $hazard;
            }
        }

        return null;
    }
}
