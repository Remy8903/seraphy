<?php

namespace App\Http\Requests;

use App\Enums\ClinicalHours\AgeGroup;
use App\Enums\ClinicalHours\ClinicMode;
use App\Enums\ClinicalHours\HoursType;
use App\Enums\ClinicalHours\LogMode;
use App\Enums\ClinicalHours\PlacementType;
use App\Enums\ClinicalHours\PracticumType;
use App\Enums\ClinicalHours\SessionMode;
use App\Enums\ClinicalHours\SessionType;
use App\Enums\ClinicalHours\WeekDay;
use App\Models\ClinicalLogForm;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ClinicalLogFormRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'mode' => ['required', Rule::enum(LogMode::class)],
            'year' => ['required', 'integer', 'between:1,4'],
            'semester' => ['required', 'integer', 'between:1,2'],
            'session_start_year' => ['required', 'integer', 'between:2000,2100'],
            'practicum_type' => ['required', Rule::enum(PracticumType::class)],
            'hours_type' => ['required', Rule::enum(HoursType::class)],
        ];

        if ($this->isSimpleMode()) {
            return [
                ...$rules,
                'placement_name' => ['nullable', 'string', 'max:255'],
                'hours' => ['required', 'integer', 'between:0,9999'],
                'minutes' => ['required', 'integer', 'between:0,59'],
            ];
        }

        return [
            ...$rules,
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['required', 'distinct', Rule::enum(WeekDay::class)],
            'placement' => ['required', Rule::enum(PlacementType::class)],
            'clinic_mode' => ['required', Rule::enum(ClinicMode::class)],
            'placement_name' => ['required', 'string', 'max:255'],
            'supervisor_name' => ['required', 'string', 'max:255'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.id' => $this->logForm() instanceof ClinicalLogForm
                ? ['nullable', 'integer', 'distinct', Rule::exists('clinical_log_entries', 'id')->where('clinical_log_form_id', $this->logForm()->id)]
                : ['exclude'],
            'entries.*.date' => ['required', 'date'],
            'entries.*.age_group' => ['required', Rule::enum(AgeGroup::class)],
            'entries.*.rn_no' => ['nullable', 'string', 'max:50'],
            'entries.*.session_mode' => ['required', Rule::enum(SessionMode::class)],
            'entries.*.session_type' => ['required', Rule::enum(SessionType::class)],
            'entries.*.diagnosis' => ['nullable', 'string', 'max:255'],
            'entries.*.hours' => ['required', 'integer', 'between:0,24'],
            'entries.*.minutes' => ['required', 'integer', 'between:0,59'],
        ];
    }

    /**
     * Get the custom validation messages for the log entries.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'entries.required' => 'Add at least one session to the log.',
            'entries.min' => 'Add at least one session to the log.',
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->isSimpleMode()) {
                    if ($this->isZeroDuration($validator, 'hours', 'minutes')) {
                        $validator->errors()->add('minutes', 'Enter at least one minute of clinical hours.');
                    }

                    return;
                }

                if ($validator->errors()->has('entries')) {
                    return;
                }

                foreach (array_keys($this->input('entries', [])) as $index) {
                    if ($this->isZeroDuration($validator, "entries.{$index}.hours", "entries.{$index}.minutes")) {
                        $validator->errors()->add("entries.{$index}.minutes", 'The session must last at least one minute.');
                    }
                }
            },
        ];
    }

    /**
     * Get the validated form header attributes, clearing the fields the chosen mode does not use.
     *
     * @return array<string, mixed>
     */
    public function formAttributes(): array
    {
        if ($this->isSimpleMode()) {
            return [
                ...$this->safe()->except(['hours', 'minutes']),
                'placement_name' => $this->validated('placement_name'),
                'days' => null,
                'placement' => null,
                'clinic_mode' => null,
                'supervisor_name' => null,
                'simple_minutes' => (int) $this->validated('hours') * 60 + (int) $this->validated('minutes'),
            ];
        }

        return [
            ...$this->safe()->except('entries'),
            'simple_minutes' => null,
        ];
    }

    /**
     * Get the validated entries with their duration converted to minutes.
     *
     * Entries that already exist on the form carry their `id`; new entries have a null `id`.
     *
     * @return list<array<string, mixed>>
     */
    public function entryAttributes(): array
    {
        if ($this->isSimpleMode()) {
            return [];
        }

        return array_map(fn (array $entry): array => [
            'id' => isset($entry['id']) ? (int) $entry['id'] : null,
            'date' => $entry['date'],
            'age_group' => $entry['age_group'],
            'rn_no' => $entry['rn_no'] ?? null,
            'session_mode' => $entry['session_mode'],
            'session_type' => $entry['session_type'],
            'diagnosis' => $entry['diagnosis'] ?? null,
            'duration_minutes' => (int) $entry['hours'] * 60 + (int) $entry['minutes'],
        ], array_values($this->validated('entries')));
    }

    /**
     * Get the form being updated, or null when a new form is being created.
     */
    private function logForm(): ?ClinicalLogForm
    {
        $form = $this->route('clinicalLogForm');

        return $form instanceof ClinicalLogForm ? $form : null;
    }

    /**
     * Determine whether the student keyed in the hours directly instead of logging sessions.
     */
    private function isSimpleMode(): bool
    {
        return $this->enum('mode', LogMode::class) === LogMode::Simple;
    }

    /**
     * Determine whether a duration is zero hours and zero minutes, skipping fields that already failed validation.
     */
    private function isZeroDuration(Validator $validator, string $hoursKey, string $minutesKey): bool
    {
        if ($validator->errors()->hasAny([$hoursKey, $minutesKey])) {
            return false;
        }

        return (int) $this->input($hoursKey) === 0 && (int) $this->input($minutesKey) === 0;
    }
}
