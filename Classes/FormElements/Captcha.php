<?php

namespace Ahorn\FriendlyCaptcha\FormElements;

use Ahorn\FriendlyCaptcha\Service\FriendlyCaptchaVerificationService;
use Neos\Flow\Annotations as Flow;
use Neos\Error\Messages\Error;
use Neos\Form\Core\Model\AbstractFormElement;
use Neos\Form\Core\Runtime\FormRuntime;

class Captcha extends AbstractFormElement
{

    /**
     * @Flow\InjectConfiguration()
     * @var array
     */
    protected $settings = [];

    /**
     * @Flow\Inject
     * @var FriendlyCaptchaVerificationService
     */
    protected $friendlyCaptchaVerificationService;

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
        
        $overrideKeys = !empty($properties['overrideKeys']);

        $apiKey = $overrideKeys && !empty($properties['overrideApiKey'])
            ? $properties['overrideApiKey']
            : ($this->settings['apiKey'] ?? null);

        $apiEndpoint = $overrideKeys && !empty($properties['overrideApiEndpoint'])
            ? $properties['overrideApiEndpoint']
            : ($this->settings['apiEndpoint'] ?? 'global');

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


        $response = $this->friendlyCaptchaVerificationService->verifyV2(
            $elementValue,
            $apiKey,
            null,
            $apiEndpoint
        );

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

}
