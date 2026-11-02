<?php

namespace App\Enums\Therapy;

use App\Concerns\HasEnumOptions;

enum Language: string
{
    use HasEnumOptions;

    case Malay = 'ms';
    case English = 'en';

    /**
     * Get the label shown to the clinician.
     */
    public function label(): string
    {
        return match ($this) {
            self::Malay => 'Bahasa Melayu',
            self::English => 'English',
        };
    }
}
