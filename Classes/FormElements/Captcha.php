<?php

namespace Ahorn\FriendlyCaptcha\FormElements;

use Neos\Flow\Annotations as Flow;
use Neos\Error\Messages\Error;
use Neos\Form\Core\Model\AbstractFormElement;
use Neos\Form\Core\Runtime\FormRuntime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Captcha extends AbstractFormElement
{

    /**
     * @Flow\InjectConfiguration()
     * @var array
     */
    protected $settings = [];

    /**
     * Check the friendly captcha solution before submitting form.
     *
     * @param FormRuntime $formRuntime The current form runtime
     * @param mixed       $elementValue The transmitted value of the form field.
     *
     * @return void
     */

    public function onSubmit(FormRuntime $formRuntime, &$elementValue)
    {
        $properties = $this->getProperties();
        if($properties['overrideKeys'] && isset($properties['overrideSecretKey'])) {
          $apiKey = $properties['overrideSecretKey'];
        } else {
          $apiKey = $properties['apiKey'] ? $properties['apiKey'] : null;
        }

        if($properties['overrideKeys'] && isset($properties['overrideApiEndpoint'])) {
          $apiEndpoint = $properties['overrideApiEndpoint'];
        } else {
          $apiEndpoint = $properties['apiEndpoint'];
        }

        if (empty($apiKey) || $apiKey == 'add-your-api-key') {
            $processingRule = $this->getRootForm()->getProcessingRule($this->getIdentifier());
            $processingRule->getProcessingMessages()->addError(new Error('Error. Please try again later.', 17942348245));
            return;
        }

        if (empty($elementValue)) {
          $processingRule = $this->getRootForm()->getProcessingRule($this->getIdentifier());
          $processingRule->getProcessingMessages()->addError(new Error('You forgot to add the solution parameter.', 1515642243));
          return;
        }


        $verify = $this->verifyCaptchaSolutionV2('https://'.$apiEndpoint.'.frcapi.com/api/v2/captcha/siteverify', $elementValue, $apiKey);
        $response = $verify ? json_decode($verify, true) : [];

        if (empty($response)) {
            $processingRule = $this->getRootForm()->getProcessingRule($this->getIdentifier());
            $processingRule->getProcessingMessages()->addError(new Error('Validation server is not responding.', 1735489214));
            return;
        }

        if (!$response['success']) {
            $code = $response['error']['error_code'] ?? 'bad_request';
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
            $processingRule = $this->getRootForm()->getProcessingRule($this->getIdentifier());
            $processingRule->getProcessingMessages()->addError(new Error($code, $errorId));
        }
    }

    /**
     * Verify the generated solution with Friendly Captcha API.
     *
     * @param string $url Friendly Captcha verify url
     * @param string $response string with value of friendlyCaptcha Widget
     * @param string $apiKey a string with the api key
     *
     * @return bool|string
     */

    public function verifyCaptchaSolutionV2($url, $response, $apiKey)
    {

        $data = ['response' => $response];
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
}
