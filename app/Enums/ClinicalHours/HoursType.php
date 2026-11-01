<?php

namespace App\Enums\ClinicalHours;

use App\Concerns\HasEnumOptions;

enum HoursType: string
{
    use HasEnumOptions;

    case Observation = 'observation';
    case HandsOn = 'hands_on';

    /**
     * Get the label shown on the log form.
     */
    public function label(): string
    {
        return match ($this) {
            self::Observation => 'Observation',
            self::HandsOn => 'Hands-On',
        };
    }
}
