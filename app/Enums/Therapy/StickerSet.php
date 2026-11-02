<?php

namespace App\Enums\Therapy;

use App\Concerns\HasEnumOptions;

enum StickerSet: string
{
    use HasEnumOptions;

    case Animals = 'animals';
    case Vehicles = 'vehicles';
    case Space = 'space';

    /**
     * Get the label shown to the clinician.
     */
    public function label(): string
    {
        return match ($this) {
            self::Animals => 'Animals',
            self::Vehicles => 'Vehicles',
            self::Space => 'Space',
        };
    }

    /**
     * Get the stickers the child can choose from.
     *
     * @return list<string>
     */
    public function stickers(): array
    {
        return match ($this) {
            self::Animals => ['🐶', '🐱', '🐰', '🐼', '🦁', '🐯', '🐸', '🐵', '🦊', '🐨', '🐧', '🦄'],
            self::Vehicles => ['🚗', '🚌', '🚒', '🚓', '🚑', '🚜', '🚂', '🚀', '✈️', '🚁', '⛵', '🚲'],
            self::Space => ['🚀', '🛸', '🌍', '🌙', '⭐', '🌟', '☄️', '🪐', '👽', '🛰️', '🌞', '👩‍🚀'],
        };
    }
}
