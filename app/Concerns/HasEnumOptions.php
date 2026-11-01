<?php

namespace App\Concerns;

trait HasEnumOptions
{
    /**
     * Get the human-readable label for the case.
     */
    abstract public function label(): string;

    /**
     * Get every case as a value/label pair for select inputs.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
