<?php

namespace App\Services;

use App\Enums\ClinicalHours\HoursType;
use App\Enums\ClinicalHours\PracticumType;
use App\Models\ClinicalLogForm;
use Illuminate\Support\Collection;

class ClinicalHoursSummary
{
    /**
     * The tracker rows in display order, matching the student's hours spreadsheet.
     *
     * @var array<string, string>
     */
    private const array PERIODS = [
        'y1s1' => 'Y1 Sem 1',
        'y1s2' => 'Y1 Sem 2',
        'y2s1' => 'Y2 Sem 1',
        'y2s2' => 'Y2 Sem 2',
        'y3s1' => 'Y3 Sem 1',
        'y3s2' => 'Y3 Sem 2',
        'internship' => 'Internship',
        'y4s1' => 'Y4 Sem 1',
        'y4s2' => 'Y4 Sem 2',
    ];

    /**
     * Summarize completed minutes per hours type and per tracker period.
     *
     * Each form must be loaded with `withSum('entries', 'duration_minutes')`.
     *
     * @param  Collection<int, ClinicalLogForm>  $forms
     * @return array{
     *     categories: list<array{hours_type: string, label: string, target_minutes: int, completed_minutes: int, remaining_minutes: int, percentage: float, is_completed: bool}>,
     *     periods: list<array{key: string, label: string, minutes: array<string, int>}>,
     *     totals: array<string, int>,
     * }
     */
    public function summarize(Collection $forms): array
    {
        $periodMinutes = array_map(fn (): array => $this->emptyMinutes(), self::PERIODS);

        foreach ($forms as $form) {
            $periodMinutes[$this->periodKey($form)][$form->hours_type->value] += (int) $form->entries_sum_duration_minutes;
        }

        $totals = [];

        foreach (HoursType::cases() as $hoursType) {
            $totals[$hoursType->value] = array_sum(array_column($periodMinutes, $hoursType->value));
        }

        return [
            'categories' => array_map(
                fn (HoursType $hoursType): array => $this->category($hoursType, $totals[$hoursType->value]),
                HoursType::cases(),
            ),
            'periods' => array_map(
                fn (string $key): array => [
                    'key' => $key,
                    'label' => self::PERIODS[$key],
                    'minutes' => $periodMinutes[$key],
                ],
                array_keys(self::PERIODS),
            ),
            'totals' => $totals,
        ];
    }

    /**
     * Get a zeroed minutes tally keyed by hours type.
     *
     * @return array<string, int>
     */
    private function emptyMinutes(): array
    {
        $minutes = [];

        foreach (HoursType::cases() as $hoursType) {
            $minutes[$hoursType->value] = 0;
        }

        return $minutes;
    }

    /**
     * Get the tracker row a form's hours count toward.
     */
    private function periodKey(ClinicalLogForm $form): string
    {
        if ($form->practicum_type === PracticumType::LatihanIndustri) {
            return 'internship';
        }

        return "y{$form->year}s{$form->semester}";
    }

    /**
     * Build the target progress for one hours type.
     *
     * @return array{hours_type: string, label: string, target_minutes: int, completed_minutes: int, remaining_minutes: int, percentage: float, is_completed: bool}
     */
    private function category(HoursType $hoursType, int $completedMinutes): array
    {
        $targetMinutes = (int) config("clinical_hours.targets.{$hoursType->value}") * 60;
        $remainingMinutes = max(0, $targetMinutes - $completedMinutes);

        return [
            'hours_type' => $hoursType->value,
            'label' => $hoursType->label(),
            'target_minutes' => $targetMinutes,
            'completed_minutes' => $completedMinutes,
            'remaining_minutes' => $remainingMinutes,
            'percentage' => $targetMinutes > 0 ? round($completedMinutes / $targetMinutes * 100, 1) : 0.0,
            'is_completed' => $remainingMinutes === 0,
        ];
    }
}
