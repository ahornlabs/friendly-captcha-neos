<?php
declare(strict_types=1);

namespace Ahorn\FriendlyCaptcha\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Neos\Flow\Annotations as Flow;

/**
 * Central service to verify FriendlyCaptcha v2 responses.
 */
#[Flow\Scope('singleton')]
class FriendlyCaptchaVerificationService
{
    #[Flow\InjectConfiguration(path: 'apiKey')]
    protected ?string $defaultApiKey = null;

    #[Flow\InjectConfiguration(path: 'siteKey')]
    protected ?string $defaultSiteKey = null;

    #[Flow\InjectConfiguration(path: 'apiEndpoint')]
    protected ?string $defaultApiEndpoint = null;

    /**
     * Verify a captcha response against the FriendlyCaptcha v2 API.
     *
     * @param string $captchaResponse Value of frc-captcha-response
     * @param string|null $apiKey Optional API key override
     * @param string|null $siteKey Optional site key override
     * @param string|null $apiEndpoint Optional endpoint override (global|eu|us)
     * @return array Decoded API response or an empty array on transport/decode failure
     */
    public function callVerifyApi(
        string $captchaResponse,
        ?string $apiKey = null,
        ?string $siteKey = null,
        ?string $apiEndpoint = null
    ): array {
        $resolvedApiKey = $apiKey ?? $this->defaultApiKey ?? '';
        $resolvedSiteKey = $siteKey ?? $this->defaultSiteKey ?? '';
        $resolvedApiEndpoint = $apiEndpoint ?? $this->defaultApiEndpoint ?? 'global';

        if ($captchaResponse === '' || $resolvedApiKey === '') {
            return [];
        }

        $raw = $this->sendVerifyRequest(
            'https://' . $resolvedApiEndpoint . '.frcapi.com/api/v2/captcha/siteverify',
            $captchaResponse,
            $resolvedApiKey,
            $resolvedSiteKey
        );

        if ($raw === null) {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Verify a captcha solution string and return null on success or an error ID on failure.
     *
     * Error IDs:
     *   1515642243 — solution missing
     *   1735489214 — verification server not responding
     *   see resolveErrorId() for API error codes
     *
     * @param string $solution Value of frc-captcha-response
     * @return int|null null on success, numeric error ID on failure
     */
    public function verifySolution(
        string $solution,
        ?string $apiKey = null,
        ?string $siteKey = null,
        ?string $apiEndpoint = null
    ): ?int {
        if (empty($solution)) {
            return 1515642243;
        }

        $response = $this->callVerifyApi($solution, $apiKey, $siteKey, $apiEndpoint);

        if (empty($response)) {
            return 1735489214;
        }

        if (!$response['success']) {
            return $this->resolveErrorId($response['error']['error_code'] ?? 'bad_request');
        }

        return null;
    }

    /**
     * Resolve a FriendlyCaptcha error_code to its numeric error ID.
     */
    public function resolveErrorId(string $errorCode): int
    {
        return match ($errorCode) {
            'auth_required'      => 1732156724,
            'auth_invalid'       => 5786245981,
            'sitekey_invalid'    => 7956325875,
            'response_missing'   => 8876423767,
            'response_invalid'   => 1380742852,
            'response_timeout'   => 1380742853,
            'response_duplicate' => 1185587569,
            default              => 1380742851,
        };
    }

    /**
     * Sends the verification request to FriendlyCaptcha.
     */
    protected function sendVerifyRequest(string $url, string $response, string $apiKey, string $siteKey = ''): ?string
    {
        $data = ['response' => $response];
        if ($siteKey !== '') {
            $data['sitekey'] = $siteKey;
        }

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-API-Key' => $apiKey,
        ];

        $client = new Client();

        try {
            $apiResponse = $client->post($url, [
                'headers' => $headers,
                'json' => $data,
                'timeout' => 5,
            ]);

            return $apiResponse->getBody()->getContents();
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                return $e->getResponse()->getBody()->getContents();
            }

            return null;
        }
    }
}