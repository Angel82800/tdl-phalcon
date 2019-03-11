<?php

namespace Thrust\Controllers;

use Phalcon\Http\Response;

use Thrust\Auth\Exception as AuthException;

use Thrust\Models\AttrRoles;
use Thrust\Models\AttrIndustries;
use Thrust\Models\EntUsers;
use Thrust\Models\EntOrganization;

use Thrust\Helpers\BetaSignupHelper;
use Thrust\Hanzo\Client as HanzoClient;
use Thrust\Stripe\Api as Stripe;

/**
 * Controller used handle user signup.
 */
class BetaController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateBefore('session');

        if (is_array($this->auth->getIdentity())) {
            // user is logged in
            $this->response->redirect('/');
        }
    }

    /**
     * beta user signup handler
     */
    public function indexAction()
    {
        $industries = AttrIndustries::find();

        $options = [];
        foreach($industries as $val) {
            if ($val->name != 'Home or Personal Use') {
                $options[$val->pk_id] = $val->name;
            }
        }

        $config = $this->getDi()->get('config');

        $google_pk = $config->recaptcha->publicKey;
        $google_invisible_pk = $config->recaptcha->invisible_publicKey;

        $helper = new BetaSignupHelper($options);
        $sidebar_content = $helper->getSidebarContent();
        $signupMessage = null;
        $signupErrors = [];

        $step = $this->dispatcher->getParam('step') ? $this->dispatcher->getParam('step') : 1;
        $helper->setStep($step);

        if ($this->request->isPost()) {
            // validate post data
            $validation = $helper->getValidation();
            $messages = $validation->validate($this->request->getPost());

            if (! count($messages)) {
                // We're on our way to final submit

                foreach ($this->request->getPost() as $key => $value) {
                    $signup_data[$key] = $value;
                }

                // process post data and register beta user
                $result = $this->processBetaSignup($signup_data);
                $signupMessage = isset($result['signupMessage']) ? $result['signupMessage'] : false;

                // if there're no errors, proceed to download page
                if (! $signupMessage) {
                    // login the user
                    $this->auth->check(array(
                        'email'    => $signup_data['todyl_email'],
                        'password' => $signup_data['todyl_password'],
                    ));

                    $response = new Response();
                    $response->redirect('dashboard?os=' . $signup_data['user_platform']);

                    return $response;
                }
            } else {
                foreach ($messages as $message) {
                    $signupErrors[$message->getField()][] = $message->getMessage();
                }
            }
        }

        $this->view->setVars(
            array(
                'current_step'          => $step,
                'google_pk'             => $google_pk,
                'google_invisible_pk'   => $google_invisible_pk,
                'steps'                 => $sidebar_content,
                'helper'                => $helper,
                'saved_data'            => $signup_data,
                'signupMessage'         => $signupMessage,
                'signupErrors'          => $signupErrors,
            )
        );
    }

    protected function processBetaSignup($signup_data)
    {
        // check if beta coupon has been used
        if (! isset($signup_data['promo_code']) || strtolower(substr($signup_data['promo_code'], 0, 4)) != 'beta') {
            return [
                'signupMessage' => 'No beta coupon has been used',
            ];
        }

        $stripe = new Stripe();
        $organization = new EntOrganization();
        $user = new EntUsers();

        // create stripe customer
        $customer = $stripe->createCustomer(null, $signup_data['todyl_email'], $signup_data['todyl_company_name']);

        if (!$customer) {
            $signupMessage = $this->session->get('stripe_error');
            $this->session->remove('stripe_error');

            return [
                'signupMessage' => $signupMessage,
            ];
        }

        // subscribe customer to chosen plans
        $coupon = $signup_data['promo_code'];
        $plans = [
            [
                'plan'      =>  'beta_member',
                'quantity'  =>  1,
            ],
        ];

        $subscription = $stripe->subscribeCustomer($customer->id, $plans, $coupon);

        if (! $subscription) {
            $signupMessage = $this->session->get('stripe_error');
            $this->session->remove('stripe_error');

            return [
                'signupMessage' => $signupMessage,
            ];
        }

        // create organization

        if ($signup_data['primary_use'] == 'personal') {
            $org_data = [
                'name' => 'Your Home',
                'fk_attr_industries_id' => 57, // ID for 'Home or Personal Use' in attr_industries
            ];
        } else if ($signup_data['primary_use'] == 'business') {
            $org_data = [
                'name' => $signup_data['todyl_company_name'],
                'fk_attr_industries_id' => $signup_data['business_type'],
            ];
        }

        // common organization fields
        $org_data = array_merge($org_data, [
            'zipCode' => $signup_data['todyl_office_zip'],
            'stripe_customer_id' => $customer->id,
            'is_beta' => 1,
            'devices' => 1,
            'clients' => 0,
            'employees' => 0,
            'created_by' => 'thrust',
            'updated_by' => 'thrust',
        ]);

        $organization = new EntOrganization();
        if ($organization->create($org_data) === false) {
            return [
                'signupMessage' => 'Error while creating organization<br />' . implode('<br />', $organization->getMessages()),
            ];
        }

        //-- user --

        // convert formatted phone no to literal numbers
        $phone_number = preg_replace('/[^0-9]/i', '', $signup_data['todyl_phone_no']);

        // generate password
        $password = $this->getDI()->getSecurity()->hash($signup_data['todyl_password']);

        $role = AttrRoles::findFirst([
            'conditions' => 'name = ?1 AND is_active = ?2',
            'bind'       => [
                1 => 'org_full_admin',
                2 => 1,
            ],
        ]);

        $user_data = [
            'firstName'                 => $signup_data['todyl_first_name'],
            'lastName'                  => $signup_data['todyl_last_name'],
            'email'                     => $signup_data['todyl_email'],
            'password'                  => $password,
            'primaryPhone'              => $phone_number,
            'fk_ent_organization_id'    => $organization->pk_id,
            'fk_attr_roles_id'          => $role->pk_id,
            'is_active'                 => 1,
            'is_beta'                   => 1,
            'created_by'                => 'thrust',
            'updated_by'                => 'thrust',
        ];

        $user = new EntUsers();
        if ($user->create($user_data) === false) {
            return [
                'signupMessage' => implode('<br />', $user->getMessages()),
            ];
        }

        return true;
    }

}