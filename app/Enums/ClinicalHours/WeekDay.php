<?php

namespace App\Enums\ClinicalHours;

use App\Concerns\HasEnumOptions;

enum WeekDay: string
{
    use HasEnumOptions;

    case Monday = 'mon';
    case Tuesday = 'tue';
    case Wednesday = 'wed';
    case Thursday = 'thu';
    case Friday = 'fri';

    /**
     * Get the label shown on the log form.
     */
    public function label(): string
    {
        return match ($this) {
            self::Monday => 'MON',
            self::Tuesday => 'TUE',
            self::Wednesday => 'WED',
            self::Thursday => 'THU',
            self::Friday => 'FRI',
        };
    }
}
