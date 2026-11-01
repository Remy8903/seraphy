<?php

namespace App\Enums\ClinicalHours;

use App\Concerns\HasEnumOptions;

enum AgeGroup: string
{
    use HasEnumOptions;

    case ChildAdolescent = 'child_adolescent';
    case Adult = 'adult';
    case Elderly = 'elderly';

    /**
     * Get the label shown on the log form.
     */
    public function label(): string
    {
        return match ($this) {
            self::ChildAdolescent => 'C/A',
            self::Adult => 'A',
            self::Elderly => 'E',
        };
    }
}
