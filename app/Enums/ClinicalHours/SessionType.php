<?php

namespace App\Enums\ClinicalHours;

use App\Concerns\HasEnumOptions;

enum SessionType: string
{
    use HasEnumOptions;

    case Diagnostic = 'diagnostic';
    case Therapy = 'therapy';

    /**
     * Get the label shown on the log form.
     */
    public function label(): string
    {
        return match ($this) {
            self::Diagnostic => 'Diagnostic',
            self::Therapy => 'Therapy',
        };
    }
}
