<?php

namespace App\Http\Requests;

use App\Models\PlaySession;
use App\Services\Therapy\TrialRules;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTrialsRequest extends FormRequest
{
    /**
     * Determine whether the user may record answers on the session.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->playSession());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return (new TrialRules($this->playSession()->activity))->rules();
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
                if ($this->playSession()->isFinished()) {
                    $validator->errors()->add('trials', 'This session has already ended.');
                }
            },
        ];
    }

    /**
     * Get the validated answers as trial attributes.
     *
     * @return list<array{client_uuid: string, content_item_id: int|null, group_number: int|null, activity_direction_id: int|null, result: string, response_ms: int|null, meta: string|null}>
     */
    public function trialAttributes(): array
    {
        return TrialRules::attributes($this->validated('trials'));
    }

    /**
     * Get the session the answers belong to.
     */
    public function playSession(): PlaySession
    {
        /** @var PlaySession */
        return $this->route('playSession');
    }
}
