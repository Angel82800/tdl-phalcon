<?php

namespace Thrust\Mail;

use Phalcon\Mvc\User\Component;
use Swift_Message as Message;
use Phalcon\Mvc\View;

/**
 * Thrust\Mail\Mail
 * Sends e-mails based on pre-defined templates.
 */
class Mail extends Component
{
    protected $amazonSes;

    /**
     * Send a raw e-mail via AmazonSES.
     *
     * @param string $raw
     *
     * @return bool
     */
    private function amazonSESSend($raw)
    {
        try {
            if ($this->amazonSes == null) {
                $this->amazonSes = new \Aws\Ses\SesClient([
                    'version'=> 'latest',
                    'region' => $this->config->awsSes->region,
                    'credentials'   => [
                        'key'    => $this->config->awsSes->key,
                        'secret' => $this->config->awsSes->secret,
                    ],
                ]);
                // @$this->amazonSes->disable_ssl_verification();
            }
            $response = $this->amazonSes->SendRawEmail([
                'RawMessage' => [
                    'Data' => $raw
                ],
            ]);

            return $response;
        } catch (\Exception $e) {
            $logger = \Phalcon\Di::getDefault()->getShared('logger');
            $logger->error('Error sending email from AWS SES: ' . $e->getMessage());
        }
    }

    /**
     * Applies a template to be used in the e-mail.
     *
     * @param string $name
     * @param array  $params
     *
     * @return string
     */
    public function getTemplate($name, $params)
    {
        $parameters = array_merge(array(
            'publicUrl' => $this->config->application->publicUrl,
            'absolutePath' => 'https://www.' . $this->config->application->publicUrl . $this->config->application->baseUri,
        ), $params);

        // $view = new \Phalcon\Mvc\View\Simple();
        // $view->setViewsDir($this->config->application->viewsDir);
        // $view = $this->getDI()->getView();
        // try {
        //     $view->setRenderLevel(View::LEVEL_ACTION_VIEW);
        //     $html = $view->render('emailTemplates/' . $name, $parameters);
        // } catch (\Exception $e) {
        //     exit($e->getMessage());
        // }
        // return $html;

        if (isset($params['template'])) {
            $this->view->setLayout($params['template']);
        } else {
            $this->view->setLayout('email');
        }

        return $this->view->getRender('emailTemplates', $name, $parameters, function ($view) {
            $view->setRenderLevel(View::LEVEL_LAYOUT);
        });
    }

    /**
     * Sends e-mails based on predefined templates.
     *
     * @param array  $to
     * @param string $subject
     * @param string $name
     * @param array  $params
     *
     * @return bool|int
     *
     * @throws Exception
     */
    public function send($to, $subject, $name, $params)
    {
        // if environment is 'dev', send email to 'devemail@todyl.com'

        if ($this->config->environment->env === 'dev') {
            $subject .= ' - TO : ' . print_r($to, true);
            $to = 'devemail@todyl.com';
        }

        $mailSettings = $this->config->mail;

        $template = $this->getTemplate($name, $params);

        $message = Message::newInstance()
            ->setSubject($subject)
            ->setTo($to)
            ->setFrom(array(
                $mailSettings->fromEmail => $mailSettings->fromName
            ))
            ->setBody($template, 'text/html');

        $response = $this->amazonSESSend($message->toString());

        $logger = \Phalcon\Di::getDefault()->getShared('logger');
        $logger->info('AmazonSES Response (TO: ' . $to . '): ' . print_r($response, true));

        return $response;
    }
}
