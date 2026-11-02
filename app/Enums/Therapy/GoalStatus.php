<?php

namespace App\Enums\Therapy;

use App\Concerns\HasEnumOptions;

enum GoalStatus: string
{
    use HasEnumOptions;

    case Active = 'active';
    case Achieved = 'achieved';
    case Discontinued = 'discontinued';

    /**
     * Get the label shown to the clinician.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Achieved => 'Achieved',
            self::Discontinued => 'Discontinued',
        };
    }
}
