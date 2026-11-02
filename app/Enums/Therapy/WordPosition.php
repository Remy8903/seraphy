<?php

namespace App\Enums\Therapy;

use App\Concerns\HasEnumOptions;

enum WordPosition: string
{
    use HasEnumOptions;

    case Initial = 'initial';
    case Medial = 'medial';
    case Final = 'final';

    /**
     * Get the label shown to the clinician.
     */
    public function label(): string
    {
        return match ($this) {
            self::Initial => 'Initial',
            self::Medial => 'Medial',
            self::Final => 'Final',
        };
    }
}
