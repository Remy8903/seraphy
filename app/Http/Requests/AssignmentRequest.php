<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignmentRequest extends FormRequest
{
    /**
     * Determine whether the user may change the client's plan.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('client'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Client $client */
        $client = $this->route('client');

        return [
            'activity_id' => [
                'required',
                'integer',
                Rule::exists('activities', 'id')->where('user_id', $this->user()->id),
                Rule::unique('assignments')->where('client_id', $client->id),
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
            'activity_id.unique' => 'This activity is already on the client\'s plan.',
        ];
    }
}
