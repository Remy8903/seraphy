<?php

namespace App\Http\Requests;

use App\Enums\Therapy\StickerSet;
use App\Models\PlaySession;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlaySessionStickersRequest extends FormRequest
{
    /**
     * Determine whether the user may update the session's sticker board.
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
        $stickerSet = StickerSet::tryFrom((string) ($this->playSession()->activity->settings['sticker_set'] ?? ''));

        return [
            'stickers' => ['present', 'array', 'max:100'],
            'stickers.*' => ['required', 'string', Rule::in($stickerSet?->stickers() ?? [])],
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
            'stickers.*.in' => 'Choose stickers from the activity\'s sticker set.',
        ];
    }

    /**
     * Get the session the stickers belong to.
     */
    public function playSession(): PlaySession
    {
        /** @var PlaySession */
        return $this->route('playSession');
    }
}
