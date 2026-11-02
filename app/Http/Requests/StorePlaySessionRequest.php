<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlaySessionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')->where('user_id', $this->user()->id)->whereNull('archived_at'),
            ],
            'activity_id' => [
                'required',
                'integer',
                Rule::exists('assignments', 'activity_id')->where('client_id', (int) $this->input('client_id')),
            ],
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
            'activity_id.exists' => 'Assign the activity to the client before playing it.',
        ];
    }
}
