<?php

namespace App\Http\Requests;

use App\Enums\Therapy\StickerSet;
use App\Models\Activity;
use App\Models\PlaySession;
use App\Rules\NoPersonalData;
use App\Services\Therapy\TrialRules;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator as ValidatorFactory;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SyncPlaySessionsRequest extends FormRequest
{
    /**
     * The most answers a single offline session can upload.
     */
    public const int MAX_TRIALS_PER_SESSION = 500;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sessions' => ['required', 'array', 'min:1', 'max:20'],
            'sessions.*.client_uuid' => ['required', 'uuid', 'distinct'],
            'sessions.*.client_id' => ['required', 'integer', Rule::exists('clients', 'id')->where('user_id', $this->user()->id)],
            'sessions.*.activity_id' => ['required', 'integer', Rule::exists('activities', 'id')->where('user_id', $this->user()->id)],
            'sessions.*.started_at' => ['required', 'date', 'before_or_equal:'.now()->addDay()->toIso8601String()],
            'sessions.*.ended_at' => ['required', 'date', 'after_or_equal:sessions.*.started_at'],
            'sessions.*.notes' => ['nullable', 'string', 'max:2000', new NoPersonalData],
            'sessions.*.stickers' => ['nullable', 'array', 'max:100'],
            'sessions.*.stickers.*' => ['string', 'max:20'],
            'sessions.*.trials' => ['required', 'array', 'min:1', 'max:'.self::MAX_TRIALS_PER_SESSION],
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
            'sessions.*.client_id.exists' => 'This session is for a client who is not one of yours.',
            'sessions.*.activity_id.exists' => 'This session is for an activity that is not one of yours.',
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * Each session's answers are checked against its own activity, like answers sent online.
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

                $activities = $this->activities();
                $existing = PlaySession::query()
                    ->whereIn('client_uuid', array_column($this->input('sessions'), 'client_uuid'))
                    ->get()
                    ->keyBy('client_uuid');

                foreach (array_values($this->input('sessions')) as $index => $session) {
                    $activity = $activities->get((int) $session['activity_id']);
                    $synced = $existing->get($session['client_uuid']);

                    if ($synced && $synced->client_id !== (int) $session['client_id']) {
                        $validator->errors()->add("sessions.{$index}.client_uuid", 'This session was already saved for another client.');

                        continue;
                    }

                    $trialValidator = ValidatorFactory::make(
                        ['trials' => $session['trials']],
                        (new TrialRules($activity))->rules('trials', self::MAX_TRIALS_PER_SESSION),
                    );

                    foreach ($trialValidator->errors()->messages() as $key => $messages) {
                        $validator->errors()->add("sessions.{$index}.{$key}", $messages[0]);
                    }

                    $stickers = StickerSet::tryFrom((string) ($activity->settings['sticker_set'] ?? ''))?->stickers() ?? [];

                    if (array_diff($session['stickers'] ?? [], $stickers) !== []) {
                        $validator->errors()->add("sessions.{$index}.stickers", 'Choose stickers from the activity\'s sticker set.');
                    }
                }
            },
        ];
    }

    /**
     * Get the validated sessions to sync.
     *
     * @return list<array{client_uuid: string, client_id: int, activity_id: int, started_at: string, ended_at: string, notes: string|null, stickers: list<string>|null, trials: array<int, array<string, mixed>>}>
     */
    public function sessions(): array
    {
        return array_map(fn (array $session): array => [
            'client_uuid' => $session['client_uuid'],
            'client_id' => (int) $session['client_id'],
            'activity_id' => (int) $session['activity_id'],
            'started_at' => $session['started_at'],
            'ended_at' => $session['ended_at'],
            'notes' => $session['notes'] ?? null,
            'stickers' => isset($session['stickers']) ? array_values($session['stickers']) : null,
            'trials' => $session['trials'],
        ], array_values($this->validated('sessions')));
    }

    /**
     * Get the clinician's activities the sessions were played with, keyed by id.
     *
     * @return Collection<int, Activity>
     */
    private function activities(): Collection
    {
        return $this->user()
            ->activities()
            ->with(['items', 'directions'])
            ->whereKey(array_column($this->input('sessions'), 'activity_id'))
            ->get()
            ->keyBy('id');
    }
}
