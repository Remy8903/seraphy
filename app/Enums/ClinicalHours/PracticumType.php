<?php

namespace App\Enums\ClinicalHours;

use App\Concerns\HasEnumOptions;

enum PracticumType: string
{
    use HasEnumOptions;

    case Screening = 'screening';
    case StudentBlock = 'student_block';
    case LatihanIndustri = 'latihan_industri';

    /**
     * Get the label shown on the log form.
     */
    public function label(): string
    {
        return match ($this) {
            self::Screening => 'Screening',
            self::StudentBlock => 'Student Block',
            self::LatihanIndustri => 'Latihan Industri',
        };
    }
}
