<?php

namespace App\Http\Requests;

use App\Enums\ClinicalHours\AgeGroup;
use App\Enums\Therapy\Language;
use App\Models\Client;
use App\Rules\NoPersonalData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $client = $this->route('client');

        return [
            'reference_code' => [
                'required',
                'string',
                'max:30',
                'regex:/^[A-Za-z0-9\/\- ]+$/',
                Rule::unique('clients')
                    ->where('user_id', $this->user()->id)
                    ->ignore($client instanceof Client ? $client->id : null),
                new NoPersonalData,
            ],
            'age_group' => ['required', Rule::enum(AgeGroup::class)],
            'language' => ['required', Rule::enum(Language::class)],
            'diagnosis' => ['nullable', 'string', 'max:255', new NoPersonalData],
            'notes' => ['nullable', 'string', 'max:2000', new NoPersonalData],
            'confirms_no_personal_data' => $this->isMethod('post') ? ['accepted'] : ['exclude'],
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
            'reference_code.regex' => 'The reference may only contain letters, numbers, spaces, dashes and slashes.',
            'reference_code.unique' => 'You already have a client with this reference.',
            'confirms_no_personal_data.accepted' => 'Confirm that the client details contain no information that identifies the patient.',
        ];
    }

    /**
     * Get the validated client attributes.
     *
     * @return array<string, mixed>
     */
    public function clientAttributes(): array
    {
        return $this->safe()->except('confirms_no_personal_data');
    }
}
