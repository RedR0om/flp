<?php

namespace App\Enums;

enum FlpLevel: string
{
    case Hayo = 'HAYO';
    case Hinay = 'HINAY';
    case Hinga = 'HINGA';
    case Hinto = 'HINTO';

    public function title(): string
    {
        return match ($this) {
            self::Hayo => 'CONTINUE',
            self::Hinay => 'EASE-IN',
            self::Hinga => 'CHECK-IN',
            self::Hinto => 'STOP',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Hayo => 'Regular in-person learning under safe and stable conditions.',
            self::Hinay => 'Flexible and adjusted learning during minor to moderate disruptions.',
            self::Hinga => 'Reduced academic demands, with emphasis on well-being and recovery.',
            self::Hinto => 'Learning activities are suspended to prioritize safety and emergency response.',
        };
    }

    public function label(): string
    {
        return $this->value.' – '.$this->title();
    }

    /**
     * @return list<array{value: string, title: string, description: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $level) => [
            'value' => $level->value,
            'title' => $level->title(),
            'description' => $level->description(),
            'label' => $level->label(),
        ], self::cases());
    }
}
