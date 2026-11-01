<?php

namespace App\Enums\ClinicalHours;

use App\Concerns\HasEnumOptions;

enum PlacementType: string
{
    use HasEnumOptions;

    case Internal = 'internal';
    case External = 'external';

    /**
     * Get the label shown on the log form.
     */
    public function label(): string
    {
        return match ($this) {
            self::Internal => 'INTERNAL',
            self::External => 'EXTERNAL',
        };
    }
}
