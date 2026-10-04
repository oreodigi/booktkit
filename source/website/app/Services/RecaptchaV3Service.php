<?php

namespace App\Services;

use App\Models\BasicSettings\Basic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class RecaptchaV3Service
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    private const DEFAULT_SCORE = 0.5;

    public function enabled(): bool
    {
        return (int) (Basic::query()->value('google_recaptcha_status') ?? 0) === 1;
    }

    public function siteKey(): string
    {
        return (string) (Basic::query()->value('google_recaptcha_site_key') ?? '');
    }

    public function verify(Request $request, string $expectedAction, ?float $minimumScore = null): void
    {
        if (!$this->enabled()) {
            return;
        }

        $settings = Basic::query()
            ->select('google_recaptcha_secret_key')
            ->first();

        $token = (string) $request->input('g-recaptcha-response', '');
        if ($token === '') {
            $this->fail();
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post(self::VERIFY_URL, [
                    'secret' => (string) ($settings->google_recaptcha_secret_key ?? ''),
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);

            if (!$response->successful()) {
                $this->fail();
            }

            $result = $response->json();
        } catch (\Throwable $e) {
            report($e);
            $this->fail();
        }

        $score = (float) ($result['score'] ?? 0);
        $action = (string) ($result['action'] ?? '');
        $hostname = strtolower((string) ($result['hostname'] ?? ''));
        $expectedHost = strtolower((string) $request->getHost());
        $threshold = $minimumScore ?? (float) env('RECAPTCHA_V3_MIN_SCORE', self::DEFAULT_SCORE);

        $hostnameMatches = $hostname !== '' && (
            hash_equals($expectedHost, $hostname)
            || (app()->environment('local') && in_array($hostname, ['localhost', '127.0.0.1'], true))
        );

        if (
            !($result['success'] ?? false)
            || !hash_equals($expectedAction, $action)
            || $score < $threshold
            || !$hostnameMatches
        ) {
            $this->fail();
        }
    }

    private function fail(): void
    {
        throw ValidationException::withMessages([
            'g-recaptcha-response' => __('Security verification failed. Please try again.'),
        ]);
    }
}
