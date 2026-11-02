<?php

namespace App\Http\Requests;

use App\Enums\Therapy\Language;
use App\Enums\Therapy\WordPosition;
use App\Models\ContentItem;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ContentItemRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'language' => ['required', Rule::enum(Language::class)],
            'word' => ['required', 'string', 'max:100'],
            'emoji' => ['nullable', 'string', 'max:16'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
            'remove_image' => ['boolean'],
            'target_sound' => ['nullable', 'string', 'max:10', 'regex:/^[a-z]+$/'],
            'word_position' => ['nullable', Rule::enum(WordPosition::class)],
            'category' => ['nullable', 'string', 'max:50'],
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
     * Get the "after" validation callables for the request.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['emoji', 'image'])) {
                    return;
                }

                $contentItem = $this->route('contentItem');
                $keepsExistingImage = $contentItem instanceof ContentItem
                    && $contentItem->image_path !== null
                    && ! $this->boolean('remove_image');

                if (blank($this->input('emoji')) && ! $this->hasFile('image') && ! $keepsExistingImage) {
                    $validator->errors()->add('emoji', 'Add an emoji or upload a picture.');
                }
            },
        ];
    }

    /**
     * Get the validated word attributes, without the uploaded file fields.
     *
     * @return array<string, mixed>
     */
    public function contentItemAttributes(): array
    {
        return [
            ...$this->safe()->except(['image', 'remove_image']),
            'target_sound' => $this->validated('target_sound'),
            'word_position' => $this->validated('word_position'),
            'category' => $this->validated('category'),
            'emoji' => $this->validated('emoji'),
        ];
    }
}
