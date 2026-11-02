<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Rejects text that looks like personal data of a real patient.
 *
 * Clients must be identified by a reference only, so obvious identifiers
 * such as Malaysian IC numbers, phone numbers and email addresses are refused.
 */
class NoPersonalData implements ValidationRule
{
    /**
     * Patterns for personal data, keyed by the name used in the error message.
     *
     * @var array<string, string>
     */
    private const array PATTERNS = [
        'an IC number' => '/(?<!\d)\d{6}-?\d{2}-?\d{4}(?!\d)/',
        'a phone number' => '/(?<!\d)(?:\+?6)?01\d[\s-]?\d{3,4}[\s-]?\d{4}(?!\d)/',
        'an email address' => '/[^\s@]+@[^\s@]+\.[^\s@]+/',
    ];

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        foreach (self::PATTERNS as $name => $pattern) {
            if (preg_match($pattern, $value) === 1) {
                $fail("The :attribute looks like it contains {$name}. Use a reference only, never real patient details.");

                return;
            }
        }
    }
}
