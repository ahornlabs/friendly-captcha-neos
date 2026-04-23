<?php
declare(strict_types=1);

namespace Ahorn\FriendlyCaptcha\Tests\Unit\Validation\Validator;

use Ahorn\FriendlyCaptcha\Service\FriendlyCaptchaVerificationService;
use Ahorn\FriendlyCaptcha\Validation\Validator\FriendlyCaptchaValidator;
use Neos\Flow\I18n\Translator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FriendlyCaptchaValidatorTest extends TestCase
{
    /**
     * Build a validator with injected mocks.
     *
     * The Translator mock returns the fallback string by default so tests stay
     * readable without needing real translation files.
     *
     * @param array $serviceResponse  What callVerifyApi() should return
     * @param string|null $apiKey     Simulated injected API key (null = not configured)
     */
    private function buildValidator(array $serviceResponse, ?string $apiKey = 'test-key'): FriendlyCaptchaValidator
    {
        /** @var FriendlyCaptchaVerificationService&MockObject $service */
        $service = $this->createMock(FriendlyCaptchaVerificationService::class);
        $service->method('callVerifyApi')->willReturn($serviceResponse);

        /** @var Translator&MockObject $translator */
        $translator = $this->createMock(Translator::class);
        // Return null so addTranslatedErrorById falls back to the $fallback string
        $translator->method('translateById')->willReturn(null);

        $validator = new FriendlyCaptchaValidator([]);

        $ref = new \ReflectionClass($validator);

        $setProp = static function (string $name, mixed $value) use ($ref, $validator): void {
            $prop = $ref->getProperty($name);
            $prop->setAccessible(true);
            $prop->setValue($validator, $value);
        };

        $setProp('friendlyCaptchaVerificationService', $service);
        $setProp('translator', $translator);
        $setProp('apiKey', $apiKey);

        return $validator;
    }

    // --- API key guard ---

    public function testMissingApiKeyAddsError(): void
    {
        $validator = $this->buildValidator([], null);
        $result = $validator->validate('some-token');
        $this->assertTrue($result->hasErrors());
    }

    public function testPlaceholderApiKeyAddsError(): void
    {
        $validator = $this->buildValidator([], 'add-your-api-key');
        $result = $validator->validate('some-token');
        $this->assertTrue($result->hasErrors());
    }

    // --- Empty / invalid captcha value guard ---

    public function testEmptyValueAddsError(): void
    {
        $validator = $this->buildValidator([]);
        $result = $validator->validate('');
        $this->assertTrue($result->hasErrors());
    }

    public function testUnactivatedValueAddsError(): void
    {
        $validator = $this->buildValidator([]);
        $result = $validator->validate('.UNACTIVATED');
        $this->assertTrue($result->hasErrors());
    }

    public function testActivatedValueAddsError(): void
    {
        $validator = $this->buildValidator([]);
        $result = $validator->validate('.ACTIVATED');
        $this->assertTrue($result->hasErrors());
    }

    // --- Server / API response handling ---

    public function testServerNotRespondingAddsError(): void
    {
        $validator = $this->buildValidator([]); // empty array = server not responding
        $result = $validator->validate('valid-token');
        $this->assertTrue($result->hasErrors());
    }

    public function testSuccessfulVerificationPassesValidation(): void
    {
        $validator = $this->buildValidator(['success' => true]);
        $result = $validator->validate('valid-token');
        $this->assertFalse($result->hasErrors());
    }

    public function testFailedVerificationAddsError(): void
    {
        $validator = $this->buildValidator([
            'success' => false,
            'error'   => ['error_code' => 'response_invalid'],
        ]);
        $result = $validator->validate('bad-token');
        $this->assertTrue($result->hasErrors());
    }

    /**
     * This test would have caught the verifyV2 → callVerifyApi rename bug:
     * the mock only stubs callVerifyApi(), so calling a non-existent method
     * would throw a BadMethodCallException and fail the test.
     */
    public function testValidatorCallsCallVerifyApiNotVerifyV2(): void
    {
        /** @var FriendlyCaptchaVerificationService&MockObject $service */
        $service = $this->createMock(FriendlyCaptchaVerificationService::class);
        $service->expects($this->once())
            ->method('callVerifyApi')
            ->willReturn(['success' => true]);

        /** @var Translator&MockObject $translator */
        $translator = $this->createMock(Translator::class);
        $translator->method('translateById')->willReturn(null);

        $validator = new FriendlyCaptchaValidator([]);
        $ref = new \ReflectionClass($validator);

        foreach (['friendlyCaptchaVerificationService' => $service, 'translator' => $translator, 'apiKey' => 'test-key'] as $name => $value) {
            $prop = $ref->getProperty($name);
            $prop->setAccessible(true);
            $prop->setValue($validator, $value);
        }

        $result = $validator->validate('valid-token');
        $this->assertFalse($result->hasErrors());
    }
}
