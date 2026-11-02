<?php

namespace App\Enums\Therapy;

use App\Concerns\HasEnumOptions;

/**
 * The loudness range a balloon voice activity asks for, relative to the child's
 * calibrated normal voice (not real decibels).
 */
enum VoiceLevel: string
{
    use HasEnumOptions;

    case Quiet = 'quiet';
    case Normal = 'normal';
    case Loud = 'loud';

    /**
     * Get the label shown to the clinician.
     */
    public function label(): string
    {
        return match ($this) {
            self::Quiet => 'Quieter than normal',
            self::Normal => 'Steady normal voice',
            self::Loud => 'Louder than normal',
        };
    }
}
