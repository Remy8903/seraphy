<?php

namespace App\Http\Requests;

use App\Rules\NoPersonalData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePlaySessionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:2000', new NoPersonalData],
        ];
    }
}
