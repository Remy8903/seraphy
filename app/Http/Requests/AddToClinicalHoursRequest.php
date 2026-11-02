<?php

namespace App\Http\Requests;

use App\Enums\ClinicalHours\AgeGroup;
use App\Enums\ClinicalHours\LogMode;
use App\Enums\ClinicalHours\SessionMode;
use App\Enums\ClinicalHours\SessionType;
use App\Models\Client;
use App\Services\Therapy\ClinicalHoursDraft;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AddToClinicalHoursRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'clinical_log_form_id' => [
                'required',
                'integer',
                Rule::exists('clinical_log_forms', 'id')
                    ->where('user_id', $this->user()->id)
                    ->where('mode', LogMode::LogForm->value),
            ],
            'age_group' => ['required', Rule::enum(AgeGroup::class)],
            'rn_no' => ['nullable', 'string', 'max:50'],
            'session_mode' => ['required', Rule::enum(SessionMode::class)],
            'session_type' => ['required', Rule::enum(SessionType::class)],
            'diagnosis' => ['nullable', 'string', 'max:255'],
            'hours' => ['required', 'integer', 'between:0,24'],
            'minutes' => ['required', 'integer', 'between:0,59'],
        ];
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'clinical_log_form_id.required' => 'Choose the log form to add the session to.',
            'clinical_log_form_id.exists' => 'Choose one of your log forms. Simple-mode forms cannot hold sessions.',
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
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ((int) $this->input('hours') === 0 && (int) $this->input('minutes') === 0) {
                    $validator->errors()->add('minutes', 'The session must last at least one minute.');
                }

                if ($this->unloggedSessionIds() === []) {
                    $validator->errors()->add('date', 'There are no finished sessions on this day that still need adding.');
                }
            },
        ];
    }

    /**
     * Get the validated log entry attributes with the duration converted to minutes.
     *
     * @return array<string, mixed>
     */
    public function entryAttributes(): array
    {
        return [
            'date' => $this->validated('date'),
            'age_group' => $this->validated('age_group'),
            'rn_no' => $this->validated('rn_no'),
            'session_mode' => $this->validated('session_mode'),
            'session_type' => $this->validated('session_type'),
            'diagnosis' => $this->validated('diagnosis'),
            'duration_minutes' => (int) $this->validated('hours') * 60 + (int) $this->validated('minutes'),
        ];
    }

    /**
     * Get the ids of the finished sessions on the chosen day that are not in clinical hours yet.
     *
     * @return list<int>
     */
    public function unloggedSessionIds(): array
    {
        /** @var Client $client */
        $client = $this->route('client');

        return array_values(app(ClinicalHoursDraft::class)
            ->unloggedSessions($client, (string) $this->input('date'))
            ->modelKeys());
    }
}
