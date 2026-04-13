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
    public function verifyV2(
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

        $raw = $this->verifyCaptchaSolutionV2(
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
     * Sends the verification request to FriendlyCaptcha.
     */
    protected function verifyCaptchaSolutionV2(string $url, string $response, string $apiKey, string $siteKey = ''): ?string
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