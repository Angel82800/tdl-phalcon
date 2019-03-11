<?php

namespace Thrust\Helpers;

use Phalcon\Http\Request;

use Thrust\Models\LogAgreement;

class AgreementHelper
{
    protected $di;
    protected $logger;
    protected $session;
    protected $request;

    function __construct()
    {
        $this->di = \Phalcon\DI::getDefault();
        $this->logger = \Phalcon\Di::getDefault()->getShared('logger');
        $this->request = new Request();
        $this->session = \Phalcon\Di::getDefault()->getShared('session');
    }

    public function addAgreement($user_id, $agreement_name)
    {
        // get user ip address
        $header = $this->request->getHeader('CF-Connecting-IP');
        $ip_address = ($this->request->getHeader('CF-Connecting-IP') ? $this->request->getHeader('CF-Connecting-IP') : $this->request->getClientAddress());

        $this->logger->info('[AGREEMENT] User ID ' . $user_id . ' with IP ' . $ip_address . ' agreed `' . $agreement_name . '`');

        $agreementData = [
            'fk_ent_users_id'   => $user_id,
            'agreement_name'    => $agreement_name,
            'ip_address'        => $ip_address,
            'created_by'        => 'thrust',
            'updated_by'        => 'thrust',
        ];

        $logagreement = new LogAgreement();
        if ($logagreement->create($agreementData) === false) {
            $this->logger->error('[AGREEMENT] Failed creating agreement entry ' . implode('<br />', $logagreement->getMessages()));

            return false;
        }

        return true;
    }

}
