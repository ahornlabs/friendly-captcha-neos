<?php
declare(strict_types=1);

namespace Ahorn\FriendlyCaptcha\Tests\Unit\Service;

use Ahorn\FriendlyCaptcha\Service\FriendlyCaptchaVerificationService;
use PHPUnit\Framework\TestCase;

/**
 * Testable subclass that stubs the HTTP call.
 */
class TestableFriendlyCaptchaVerificationService extends FriendlyCaptchaVerificationService
{
    public ?string $stubbedRawResponse = null;

    protected function sendVerifyRequest(string $url, string $response, string $apiKey, string $siteKey = ''): ?string
    {
        return $this->stubbedRawResponse;
    }
}

class FriendlyCaptchaVerificationServiceTest extends TestCase
{
    private TestableFriendlyCaptchaVerificationService $service;

    protected function setUp(): void
    {
        $this->service = new TestableFriendlyCaptchaVerificationService();
    }

    // --- resolveErrorId ---

    public function testResolveErrorIdKnownCodes(): void
    {
        $this->assertSame(1732156724, $this->service->resolveErrorId('auth_required'));
        $this->assertSame(5786245981, $this->service->resolveErrorId('auth_invalid'));
        $this->assertSame(7956325875, $this->service->resolveErrorId('sitekey_invalid'));
        $this->assertSame(8876423767, $this->service->resolveErrorId('response_missing'));
        $this->assertSame(1380742852, $this->service->resolveErrorId('response_invalid'));
        $this->assertSame(1380742853, $this->service->resolveErrorId('response_timeout'));
        $this->assertSame(1185587569, $this->service->resolveErrorId('response_duplicate'));
    }

    public function testResolveErrorIdReturnsDefaultForUnknownCode(): void
    {
        $this->assertSame(1380742851, $this->service->resolveErrorId('unknown_xyz'));
        $this->assertSame(1380742851, $this->service->resolveErrorId('bad_request'));
    }

    // --- callVerifyApi: early-return guards ---

    public function testCallVerifyApiReturnsEmptyArrayWhenResponseIsEmpty(): void
    {
        $result = $this->service->callVerifyApi('', 'valid-api-key');
        $this->assertSame([], $result);
    }

    public function testCallVerifyApiReturnsEmptyArrayWhenApiKeyIsEmpty(): void
    {
        $result = $this->service->callVerifyApi('some-response', '');
        $this->assertSame([], $result);
    }

    // --- callVerifyApi: HTTP stubs ---

    public function testCallVerifyApiReturnsEmptyArrayWhenServerReturnsNull(): void
    {
        $this->service->stubbedRawResponse = null;
        $result = $this->service->callVerifyApi('token', 'api-key');
        $this->assertSame([], $result);
    }

    public function testCallVerifyApiReturnsEmptyArrayOnInvalidJson(): void
    {
        $this->service->stubbedRawResponse = 'not-json';
        $result = $this->service->callVerifyApi('token', 'api-key');
        $this->assertSame([], $result);
    }

    public function testCallVerifyApiReturnsDecodedArrayOnSuccess(): void
    {
        $this->service->stubbedRawResponse = json_encode(['success' => true]);
        $result = $this->service->callVerifyApi('token', 'api-key');
        $this->assertSame(['success' => true], $result);
    }

    public function testCallVerifyApiReturnsDecodedArrayOnFailure(): void
    {
        $payload = ['success' => false, 'error' => ['error_code' => 'response_invalid']];
        $this->service->stubbedRawResponse = json_encode($payload);
        $result = $this->service->callVerifyApi('token', 'api-key');
        $this->assertSame($payload, $result);
    }

    // --- verifySolution ---

    public function testVerifySolutionReturnsErrorIdOnEmptySolution(): void
    {
        $this->assertSame(1515642243, $this->service->verifySolution(''));
    }

    public function testVerifySolutionReturnsErrorIdWhenServerNotResponding(): void
    {
        $this->service->stubbedRawResponse = null;
        $this->assertSame(1735489214, $this->service->verifySolution('token', 'api-key'));
    }

    public function testVerifySolutionReturnsNullOnSuccess(): void
    {
        $this->service->stubbedRawResponse = json_encode(['success' => true]);
        $this->assertNull($this->service->verifySolution('token', 'api-key'));
    }

    public function testVerifySolutionReturnsResolvedErrorIdOnApiFailure(): void
    {
        $this->service->stubbedRawResponse = json_encode([
            'success' => false,
            'error'   => ['error_code' => 'auth_invalid'],
        ]);
        $this->assertSame(5786245981, $this->service->verifySolution('token', 'api-key'));
    }
}
