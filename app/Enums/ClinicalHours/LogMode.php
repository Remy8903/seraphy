<?php

namespace App\Enums\ClinicalHours;

use App\Concerns\HasEnumOptions;

enum LogMode: string
{
    use HasEnumOptions;

    case LogForm = 'log_form';
    case Simple = 'simple';

    /**
     * Get the label shown on the mode toggle.
     */
    public function label(): string
    {
        return match ($this) {
            self::LogForm => 'Log form',
            self::Simple => 'Simple',
        };
    }
}
