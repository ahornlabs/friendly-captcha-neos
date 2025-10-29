<?php
declare(strict_types=1);

namespace Ahorn\FriendlyCaptcha\Validation\Validator;

use Neos\Flow\Validation\Validator\AbstractValidator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Translator;

/**
 * Validator for FriendlyCaptcha
 *
 * @api
 * @Flow\Scope("singleton")
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
            $this->addTranslatedErrorById(17002, 'Captcha missing affee.');
            return;
        }

        $raw = $this->verifyCaptchaSolutionV2(
            'https://' . $this->apiEndpoint . '.frcapi.com/api/v2/captcha/siteverify',
            $value,
            $this->apiKey
        );

        $response = $raw ? json_decode($raw, true) : [];

        if (empty($response)) {
            $this->addTranslatedErrorById(1735489214, 'Validation server is not responding.');
        }

        if (!$response['success']) {
            $code = $response['error']['error_code'] ?? 'unknown_error';
            $errorId = match ($code) {
                'auth_required'      => 1732156724,
                'auth_invalid'       => 5786245981,
                'sitekey_invalid'    => 7956325875,
                'response_missing'   => 8876423767,
                'response_invalid'   => 1380742852,
                'response_timeout'   => 1380742853,
                'response_duplicate' => 1185587569,
                'bad_request'        => 1380742851,
                default              => 1380742851,
            };
            $this->addTranslatedErrorById($errorId, $code);
        }
    }

     private function verifyCaptchaSolutionV2($url, $response, $apiKey)
    {

        $data = ['response' => $response];
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-API-Key' => $apiKey,
        ];

        $client = new Client();
        $verify = true;

        try {
            $apiResponse = $client->post($url, [
                'headers' => $headers,
                'json' => $data,
                'timeout' => 5,
                'verify' => $verify,
            ]);

            $body = $apiResponse->getBody()->getContents();

            return $body;

        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $errorBody = $e->getResponse()->getBody()->getContents();
                return $errorBody;
            } else {
                return null;
            }
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
