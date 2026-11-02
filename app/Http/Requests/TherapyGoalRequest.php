<?php

namespace App\Http\Requests;

use App\Enums\Therapy\GameType;
use App\Enums\Therapy\Language;
use App\Enums\Therapy\WordPosition;
use App\Rules\NoPersonalData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TherapyGoalRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255', new NoPersonalData],
            'game_type' => ['nullable', Rule::enum(GameType::class)],
            'language' => ['nullable', Rule::enum(Language::class)],
            'target_sound' => ['nullable', 'string', 'max:10', 'regex:/^[a-z]+$/'],
            'word_position' => ['nullable', Rule::enum(WordPosition::class)],
            'target_accuracy' => ['required', 'integer', 'between:1,100'],
            'consecutive_sessions' => ['required', 'integer', 'between:1,10'],
            'min_trials_per_session' => ['required', 'integer', 'between:1,100'],
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
            'target_sound.regex' => 'Write the target sound in lowercase letters, e.g. s, k or ng.',
        ];
    }

    /**
     * Get the validated goal attributes, storing unused filters as null.
     *
     * @return array<string, mixed>
     */
    public function goalAttributes(): array
    {
        return [
            'description' => $this->validated('description'),
            'game_type' => $this->validated('game_type'),
            'language' => $this->validated('language'),
            'target_sound' => $this->validated('target_sound'),
            'word_position' => $this->validated('word_position'),
            'target_accuracy' => (int) $this->validated('target_accuracy'),
            'consecutive_sessions' => (int) $this->validated('consecutive_sessions'),
            'min_trials_per_session' => (int) $this->validated('min_trials_per_session'),
        ];
    }
}
