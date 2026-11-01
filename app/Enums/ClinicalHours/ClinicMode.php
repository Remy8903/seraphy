<?php

namespace App\Enums\ClinicalHours;

use App\Concerns\HasEnumOptions;

enum ClinicMode: string
{
    use HasEnumOptions;

    case Tele = 'tele';
    case FaceToFace = 'f2f';
    case Hybrid = 'hybrid';

    /**
     * Get the label shown on the log form.
     */
    public function label(): string
    {
        return match ($this) {
            self::Tele => 'TELE',
            self::FaceToFace => 'F2F',
            self::Hybrid => 'HYBRID',
        };
    }
}
