<?php

namespace App\Enums\ClinicalHours;

use App\Concerns\HasEnumOptions;

enum SessionMode: string
{
    use HasEnumOptions;

    case Teletherapy = 'teletherapy';
    case FaceToFace = 'face_to_face';

    /**
     * Get the label shown on the log form.
     */
    public function label(): string
    {
        return match ($this) {
            self::Teletherapy => 'Teletherapy',
            self::FaceToFace => 'Face to Face',
        };
    }
}
