<?php
/**
 * Helper library for mailing logic
 * log every email sent out
 */

namespace Thrust\Helpers;

use Phalcon\Mvc\View;

use Thrust\Models\EntEmail;
use Thrust\Models\EntUsers;
use Thrust\Models\LogEmails;

class MailHelper
{
    protected $config;
    protected $di;
    protected $logger;

    function __construct()
    {
        $this->config = \Phalcon\Di::getDefault()->get('config');
        $this->di = \Phalcon\DI::getDefault();
        $this->logger = \Phalcon\Di::getDefault()->getShared('logger');

    }

    /**
     * Send Mail
     * @param  integer $user_id    - User to send email to
     * @param  string  $email_name - Email name
     * @param  mixed   $email_data - Email template data
     * @param  string  $template   - Email template to use
     * @return boolean
     */
    public function sendMail($user_id, $email_name, $email_data)
    {
        if (isset($email_data['template'])) $template = $email_data['template'];
        else $template = 'email2';

        $email = EntEmail::findFirst([
            'conditions' => 'name = ?1 AND is_active = 1',
            'bind'       => [
                1 => $email_name,
            ],
            'cache'     => false,
        ]);

        $user = EntUsers::findFirst([
            'conditions' => 'pk_id = ?1',
            'bind'       => [
                1 => $user_id,
            ],
            'cache'     => false,
        ]);

        // log in app logs
        $this->logger->info('[MAIL] Sending `' . $email->subject . '` email to ' . $user->email);

        // log email sent

        $logData = [
            'fk_ent_users_id'   => $user_id,
            'fk_ent_email_id'   => $email->pk_id,
            'created_by'        => 'thrust',
            'updated_by'        => 'thrust',
        ];

        $log = new LogEmails();
        if ($log->create($logData) === false) {
            $this->logger->info('[MAIL] Failed creating email log in DB :' . implode("\n", $log->getMessages()));
            return false;
        }

        // send email

        $email_data = array_merge($email_data, [
            'template' => $template,
        ]);

        $mail_sent = $this->di
            ->getMail()
            ->send([
                $user->email => $user->firstName . ' ' . $user->lastName
            ],
            $email->subject,
            // email template file name - use `name` field for this
            $email->name,
            $email_data
        );

        // refresh view layout
        $view = new View();
        $view->setLayout('');

        return $mail_sent;
    }

    /**
     * Send custom email to a custom address - it won't be logged in log_emails
     * @param  string  $to_address     - Recipient email address
     * @param  string  $subject        - Email subject
     * @param  string  $email_template - Email template name
     * @param  mixed   $email_data     - Email template data
     * @param  string  $layout         - Base layout
     * @return boolean
     */
    public function sendCustomMail($to_address, $subject, $email_template, $email_data, $layout = 'email2')
    {
        // log in app logs
        $this->logger->info('[MAIL] Sending `' . $subject . '` email to ' . $to_address);

        // send email

        $email_data = array_merge($email_data, [
            'template' => $layout,
        ]);

        $mail_sent = $this->di
            ->getMail()
            ->send([
                $to_address,
            ],
            $subject,
            $email_template,
            $email_data
        );

        // refresh view layout
        $view = new View();
        $view->setLayout('');

        return $mail_sent;

    }

}
