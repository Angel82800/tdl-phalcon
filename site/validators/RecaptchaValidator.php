<?php

namespace Thrust\validators;

use Phalcon\Validation\Validator;
use Phalcon\Validation\ValidatorInterface;
use Phalcon\Validation\Message;

class RecaptchaValidator extends Validator implements ValidatorInterface
{
    public function validate(\Phalcon\Validation $validation, $attribute)
    {
        if (!$this->isValid($validation)) {
            $message = $this->getOption('message');
            if (!$message) {
                $message = 'Please prove you are human';
            }

            $validation->appendMessage(new Message($message, $attribute, 'Recaptcha'));

            return false;
        }

        return true;
    }

    public function isValid($validation)
    {
        try {
            $config = $validation->config->recaptcha;
            $value = $validation->getValue('g-recaptcha-response');
            $ip = $validation->request->getClientAddress();

            $url = $config->verifyUrl;
            $data = [
                        'secret'   => $config->secretKey,
                        'response' => $value,
                        'remoteip' => $ip,
                    ];

            // Prepare POST request
            $options = [
                'http' => [
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($data),
                ],
            ];

            // Make POST request and evaluate the response
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);

            return json_decode($result)->success;
        } catch (Exception $e) {
            return;
        }
    }
}
