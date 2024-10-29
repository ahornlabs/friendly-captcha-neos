<?php

namespace Ahorn\FriendlyCaptcha\FormElements;

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
        if ($this->settings['apiKey']) {
            $apiKey = $properties['apiKey'] ?: $this->settings['apiKey'];
        } else {
            $apiKey = $properties['apiKey'] ?: null;
        }

        $defaultServer = $properties['defaultServer'] ? $properties['defaultServer'] : 'eu';

        if (empty($apiKey) || $apiKey == 'add-your-api-key') {
            $processingRule = $this->getRootForm()->getProcessingRule($this->getIdentifier());
            $processingRule->getProcessingMessages()->addError(new Error('Error. Please try again later.', 17942348245));
            return;
        }
        
        $params = array('solution' => $elementValue);
        $query = http_build_query($params, '', '&');

        $result = ['verified' => false, 'error' => ''];

        if (empty($params['solution'])) {
          $processingRule = $this->getRootForm()->getProcessingRule($this->getIdentifier());
          $processingRule->getProcessingMessages()->addError(new Error('You forgot to add the solution parameter.', 1515642243));
          return; 
        }
        
        $verify = $this->verifyCaptchaSolutionV2('https://'.$defaultServer.'.frcapi.com/api/v2/captcha/siteverify', $query, $apiKey);
        $response = $verify ? json_decode($verify, true) : [];

        if (empty($response)) {
            $processingRule = $this->getRootForm()->getProcessingRule($this->getIdentifier());
            $processingRule->getProcessingMessages()->addError(new Error('Validation server is not responding.', 1735489214));
            return;
        }

        if ($response['success']) {
            $result['verified'] = true;
        } else {
            $result['error'] = is_array($response['errors']) ?
                reset($response['errors']) :
                $response['errors'];
        }
        

        if ($result['verified'] === false) {

            if ($result['error']['error_code'] === 'auth_required') {
            } elseif($result['error']['error_code'] === 'auth_required') {
            } elseif($result['error']['error_code'] === 'auth_invalid') {
            } elseif($result['error']['error_code'] === 'sitekey_invalid') {
            } elseif($result['error']['error_code'] === 'response_missing') {
              $processingRule = $this->getRootForm()->getProcessingRule($this->getIdentifier());
              $processingRule->getProcessingMessages()->addError(new Error($result['error'], 8876423767));
            } elseif($result['error']['error_code'] === 'response_invalid') {
              $processingRule = $this->getRootForm()->getProcessingRule($this->getIdentifier());
              $processingRule->getProcessingMessages()->addError(new Error($result['error'], 1380742852));
            } elseif($result['error']['error_code'] === 'response_timeout') {
            } elseif($result['error']['error_code'] === 'response_duplicate') {
            } elseif($result['error']['error_code'] === 'bad_request') {
            } else{

            }

            /*  
                $processingRule = $this->getRootForm()->getProcessingRule($this->getIdentifier());
                $processingRule->getProcessingMessages()->addError(new Error($result['error'], 1732156724));
            } elseif ($result['error'] === 'solution_invalid') {

            } elseif ($result['error'] === 'solution_timeout_or_duplicate') {
                $processingRule = $this->getRootForm()->getProcessingRule($this->getIdentifier());
                $processingRule->getProcessingMessages()->addError(new Error($result['error'], 1380742853));
            } else {
                $processingRule = $this->getRootForm()->getProcessingRule($this->getIdentifier());
                $processingRule->getProcessingMessages()->addError(new Error((string)$result['error'], 1380742851));
            }
            */
        }
    }

    /**
     * Verify the generated solution with Friendly Captcha API.
     *
     * @param string $url Friendly Captcha verify url
     * @param string $options Query string with options like secret key
     *
     * @return bool|string
     */

    public function verifyCaptchaSolutionV2($url, $options, $apiKey)
    {
        $ch = curl_init();
        $headers = [
          'X-API-Key:	'.$$apiKey
        ];
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $options);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response =  curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}
