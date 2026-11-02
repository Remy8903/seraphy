<?php

namespace App\Enums\Therapy;

use App\Concerns\HasEnumOptions;

enum TrialResult: string
{
    use HasEnumOptions;

    case Correct = 'correct';
    case Prompted = 'prompted';
    case Incorrect = 'incorrect';

    /**
     * Get the label shown to the clinician.
     */
    public function label(): string
    {
        return match ($this) {
            self::Correct => 'Correct',
            self::Prompted => 'Correct with prompt',
            self::Incorrect => 'Incorrect',
        };
    }
}
