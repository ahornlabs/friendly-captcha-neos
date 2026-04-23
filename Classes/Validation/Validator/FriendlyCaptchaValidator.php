<?php
declare(strict_types=1);

namespace Ahorn\FriendlyCaptcha\Validation\Validator;

use Ahorn\FriendlyCaptcha\Service\FriendlyCaptchaVerificationService;
use Neos\Flow\Validation\Validator\AbstractValidator;
use Psr\Log\LoggerInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Translator;

/**
 * Validator for FriendlyCaptcha
 *
 * @api
 * @Flow\Scope("prototype")
 */
class FriendlyCaptchaValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [ 
        'apiKey'      => [null, 'Override API key', 'string', false],
        'siteKey'     => [null, 'Override site key', 'string', false],
        'apiEndpoint' => [null, 'Override endpoint: global|eu|us', 'string', false],
    ];

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    #[Flow\Inject]
    protected Translator $translator; 

    #[Flow\InjectConfiguration(path: 'apiKey')]
    protected ?string $apiKey = null;

    #[Flow\InjectConfiguration(path: 'siteKey')]
    protected ?string $siteKey = null;

    #[Flow\InjectConfiguration(path: 'apiEndpoint')]
    protected ?string $apiEndpoint = null;

    /**
     * @Flow\Inject
     * @var FriendlyCaptchaVerificationService
     */
    protected $friendlyCaptchaVerificationService;

    protected $acceptsEmptyValues = false;

    protected $supportsEmptyValues = false; 

    protected function initializeObject(): void
    {
        $this->apiKey      = $this->options['apiKey']      ?? $this->apiKey;
        $this->siteKey     = $this->options['siteKey']     ?? $this->siteKey;
        $this->apiEndpoint = $this->options['apiEndpoint'] ?? $this->apiEndpoint;
    }

    protected function isValid($value): void
    {
        
        if (empty($this->apiKey) || $this->apiKey == 'add-your-api-key') {
            $this->addTranslatedErrorById(17001, 'Missing API key.');
            return;
        }
         if (!is_string($value) || $value === ''||  $value === '.UNACTIVATED' || $value === '.ACTIVATED') {
            $this->addTranslatedErrorById(17002, 'Captcha missing.');
            return;
        }

        $response = $this->friendlyCaptchaVerificationService->callVerifyApi(
            $value,
            $this->apiKey,
            $this->siteKey,
            $this->apiEndpoint
        );

        if (empty($response)) {
            $this->addTranslatedErrorById(1735489214, 'Validation server is not responding.');
            return;
        }

        if (!$response['success']) {
            $code = $response['error']['error_code'] ?? 'unknown_error';
            $errorId = $this->friendlyCaptchaVerificationService->resolveErrorId($code);
            $this->addTranslatedErrorById($errorId, $code);
        }
    }

    private function addTranslatedErrorById(int $id, string $fallback = 'Validation error'): void
    {
        $msg = $this->translator->translateById(
            (string)$id,
            [],               
            null,             
            null,             
            'ValidationErrors',
            'Ahorn.FriendlyCaptcha' 
        ) ?? $fallback;

        $this->addError($msg, $id);
    }
}
