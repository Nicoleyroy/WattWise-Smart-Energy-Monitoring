<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class RecaptchaV2 implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.recaptcha.secret');
        if (!$secret || !is_string($value) || trim($value) === '') {
            $fail('The reCAPTCHA verification failed. Please try again.');
            return;
        }

        try {
            $response = Http::timeout(10)
                ->withOptions(['verify' => false])
                ->asForm()
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secret,
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);

            if (!$response->successful() || !$response->json('success')) {
                $fail('The reCAPTCHA verification failed. Please try again.');
            }
        } catch (\Throwable $exception) {
            report($exception);
            $fail('reCAPTCHA is temporarily unavailable. Please try again later.');
        }
    }
}
