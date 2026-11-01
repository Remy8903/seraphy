<?php

namespace App\Http\Requests;

use App\Enums\ClinicalHours\AgeGroup;
use App\Enums\ClinicalHours\ClinicMode;
use App\Enums\ClinicalHours\HoursType;
use App\Enums\ClinicalHours\PlacementType;
use App\Enums\ClinicalHours\PracticumType;
use App\Enums\ClinicalHours\SessionMode;
use App\Enums\ClinicalHours\SessionType;
use App\Enums\ClinicalHours\WeekDay;
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
        return [
            'year' => ['required', 'integer', 'between:1,4'],
            'semester' => ['required', 'integer', 'between:1,2'],
            'session_start_year' => ['required', 'integer', 'between:2000,2100'],
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['required', 'distinct', Rule::enum(WeekDay::class)],
            'placement' => ['required', Rule::enum(PlacementType::class)],
            'clinic_mode' => ['required', Rule::enum(ClinicMode::class)],
            'placement_name' => ['required', 'string', 'max:255'],
            'supervisor_name' => ['required', 'string', 'max:255'],
            'practicum_type' => ['required', Rule::enum(PracticumType::class)],
            'hours_type' => ['required', Rule::enum(HoursType::class)],
            'entries' => ['required', 'array', 'min:1'],
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
                if ($validator->errors()->has('entries')) {
                    return;
                }

                foreach ($this->input('entries', []) as $index => $entry) {
                    if ($validator->errors()->hasAny(["entries.{$index}.hours", "entries.{$index}.minutes"])) {
                        continue;
                    }

                    if ((int) $entry['hours'] === 0 && (int) $entry['minutes'] === 0) {
                        $validator->errors()->add("entries.{$index}.minutes", 'The session must last at least one minute.');
                    }
                }
            },
        ];
    }

    /**
     * Get the validated form header attributes.
     *
     * @return array<string, mixed>
     */
    public function formAttributes(): array
    {
        return $this->safe()->except('entries');
    }

    /**
     * Get the validated entries with their duration converted to minutes.
     *
     * @return list<array<string, mixed>>
     */
    public function entryAttributes(): array
    {
        return array_map(fn (array $entry): array => [
            'date' => $entry['date'],
            'age_group' => $entry['age_group'],
            'rn_no' => $entry['rn_no'] ?? null,
            'session_mode' => $entry['session_mode'],
            'session_type' => $entry['session_type'],
            'diagnosis' => $entry['diagnosis'] ?? null,
            'duration_minutes' => (int) $entry['hours'] * 60 + (int) $entry['minutes'],
        ], array_values($this->validated('entries')));
    }
}
