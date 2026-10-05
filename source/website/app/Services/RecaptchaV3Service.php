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
    private const SETTINGS_UNIQID = 12345;

    private function settings()
    {
        return Basic::query()
            ->where('uniqid', self::SETTINGS_UNIQID)
            ->select('google_recaptcha_status', 'google_recaptcha_site_key', 'google_recaptcha_secret_key')
            ->first();
    }

    private function stagingTestMode(): bool
    {
        return app()->environment('staging')
            && config('staging.recaptcha_test_mode') === true
            && parse_url(config('app.url'), PHP_URL_HOST) === 'test.booktkit.com'
            && config('database.connections.mysql.database') === 'booktkit_stage';
    }

    public function enabled(): bool
    {
        if ($this->stagingTestMode()) return false;
        return (int) ($this->settings()->google_recaptcha_status ?? 0) === 1;
    }

    public function siteKey(): string
    {
        return trim((string) ($this->settings()->google_recaptcha_site_key ?? ''));
    }

    public function verify(Request $request, string $expectedAction, ?float $minimumScore = null): void
    {
        if ($this->stagingTestMode() && $request->getHost() === 'test.booktkit.com') return;
        $settings = $this->settings();

        if ((int) ($settings->google_recaptcha_status ?? 0) !== 1) {
            return;
        }

        $secretKey = trim((string) ($settings->google_recaptcha_secret_key ?? ''));
        $token = (string) $request->input('g-recaptcha-response', '');

        if ($secretKey === '' || $token === '') {
            $this->fail();
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post(self::VERIFY_URL, [
                    'secret' => $secretKey,
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
        $hostname = $this->normalizeHostname((string) ($result['hostname'] ?? ''));
        $expectedHost = $this->normalizeHostname((string) $request->getHost());
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

    private function normalizeHostname(string $hostname): string
    {
        $hostname = strtolower(trim($hostname));

        return str_starts_with($hostname, 'www.') ? substr($hostname, 4) : $hostname;
    }

    private function fail(): void
    {
        throw ValidationException::withMessages([
            'g-recaptcha-response' => __('Security verification failed. Please try again.'),
        ]);
    }
}
