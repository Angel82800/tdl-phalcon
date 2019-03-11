<?php

namespace Thrust\Controllers;

use Phalcon\Http\Response;

use Thrust\Models\AttrRoles;
use Thrust\Models\EntUsers;
use Thrust\Models\EntOrganization;

use Thrust\Auth\Exception as AuthException;
use Thrust\Stripe\Api as Stripe;

use Thrust\Helpers\PricingHelper;
use Thrust\Helpers\SignupHelper;

/**
 * Controller used handle user signup.
 */
class RegistrationController extends ControllerBase
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
	 * user signup main handler
	 */
	public function indexAction()
	{
        $config = $this->getDi()->get('config');

        $stripe_pk = $config->stripe->publishKey;
        $google_pk = $config->recaptcha->publicKey;
        $google_invisible_pk = $config->recaptcha->invisible_publicKey;

        $helper = new SignupHelper();
        $sidebar_content = $helper->getSidebarContent();
        $signupMessage = null;
        $signupErrors = [];

        $step = $this->dispatcher->getParam('step') ? $this->dispatcher->getParam('step') : 1;
        $helper->setStep($step);

        $signup_data = @$this->session->get('signup_data');

        $signup_data['passed_steps'] = isset($signup_data['passed_steps']) ? $signup_data['passed_steps'] : [ 0 ];
        if ($step > max($signup_data['passed_steps']) + 1) {
            // trying to skip steps?

            $response = new Response();
            $response->redirect('signup/' . (max($signup_data['passed_steps']) + 1));

            return $response;
        }

        if ($this->request->isPost()) {
            // validate post data
            $validation = $helper->getValidation();
            $messages = $validation->validate($this->request->getPost());

            if (! count($messages)) {
                if ($step < $helper->getStepcount()) {
                    // We're on our way to final submit

                    foreach ($this->request->getPost() as $key => $value) {
                        $signup_data[$key] = $value;
                    }

                    // store passed steps also
                    $signup_data['passed_steps'][] = $step;
                    $signup_data['passed_steps'] = array_unique($signup_data['passed_steps'], SORT_NUMERIC);

                    if ($step == 1) {
                        // create/update user and organization

                        $accounts = $this->handleAccounts($signup_data);

                        if (isset($accounts['signupMessage'])) {
                            $signupMessage = $accounts['signupMessage'];
                        } else {
                            $signup_data['orgId'] = $accounts['organization']->pk_id;
                            $signup_data['userId'] = $accounts['user']->pk_id;

                            // pre-populate name on card
                            if (! isset($signup_data['name_on_card'])) {
                                $signup_data['name_on_card'] = $signup_data['todyl_first_name'] . ' ' . $signup_data['todyl_last_name'];
                            }
                        }

                    } else if ($step == 2) {
                        // customer packages selection

                        // check if we're saving previous pre-pay option
                        if (! $this->request->getPost('prepay')) unset($signup_data['prepay']);

                    }

                    // if an error message is set, don't proceed
                    if (! $signupMessage) {
                        $this->session->set('signup_data', $signup_data);

                        // go to next step
                        $steps = array_keys($sidebar_content);
                        $next_step = $steps[array_search($step, $steps) + 1];

                        $response = new Response();
                        $response->redirect('signup/' . $next_step);

                        return $response;
                    }
                } else {
                    // Final submit!
                    $signup_data = @$this->session->get('signup_data');

                    foreach ($this->request->getPost() as $key => $value) {
                        $signup_data[$key] = $value;
                    }

                    $stripe = new Stripe();

                    if ($this->request->getPost('card_token')) {
                        // new card token was submitted

                        $cardToken = $signup_data['card_token'];
                        $nameOnCard = $signup_data['name_on_card'];

                        // create new stripe customer
                        $customer = $stripe->createCustomer($cardToken, $signup_data['todyl_email'], $signup_data['todyl_business_name']);

                        if (! $customer) {
                            $signupMessage = 'Error while creating customer: ' . $this->session->get('stripe_error');
                            $this->session->remove('stripe_error');
                        } else {
                            $signup_data['stripe_customer_id'] = $customer->id;
                            $signup_data['card_brand'] = $customer->sources->data[0]->brand;
                            $signup_data['card_last4'] = $customer->sources->data[0]->last4;
                            $signup_data['payment_info'] = $signup_data['card_brand'] . ' Ending in ' . $signup_data['card_last4'];
                        }
                    } else {
                        if (! isset($signup_data['payment_info'])) {
                            // no new token, no existing payment information
                            throw new \Exception('No card has been entered');
                        }
                    }

                    if (! $signupMessage) {
                        $organization = EntOrganization::findFirst([
                            'conditions' => 'pk_id = ?1',
                            'bind'       => [
                                1 => $signup_data['orgId'],
                            ],
                            'cache'      => false,
                        ]);

                        $organization->stripe_customer_id = $signup_data['stripe_customer_id'];

                        if ($organization->update() === false) {
                            $signupMessage = 'Error while updating organization<br />' . implode('<br />', $organization->getMessages());
                        } else {
                            // subscribe customer to plans

                            $pricingHelper = new PricingHelper($signup_data['num_devices']);

                            $pricingHelper->setPrepay(isset($signup_data['prepay']));
                            $pricingHelper->setSupportLevel($signup_data['package']);

                            if (isset($signup_data['coupon'])) {
                                $pricingHelper->setCoupon($signup_data['coupon']);
                            }

                            $tier_plan = $pricingHelper->getTierPlan(isset($signup_data['prepay']) ? 'annual' : 'monthly');
                            $support_plan = $pricingHelper->getSupportPlan();

                            $plans = [
                                [
                                    'plan'      =>  $tier_plan,
                                    'quantity'  =>  $signup_data['num_devices'],
                                ],
                                [
                                    'plan'      =>  $support_plan,
                                    'quantity'  =>  1,
                                ],
                            ];

                            $coupon = isset($signup_data['coupon']) ? $signup_data['coupon']->id : false;
                            $subscription = $stripe->subscribeCustomer($signup_data['stripe_customer_id'], $plans, $coupon);

                            if ($subscription) {
                                // activate user

                                $user = EntUsers::findFirst([
                                    'conditions' => 'pk_id = ?1',
                                    'bind'       => [
                                        1 => $signup_data['userId'],
                                    ],
                                    'cache'      => false,
                                ]);

                                $user->is_active = 1;

                                if ($user->update() === false) {
                                    $signupMessage = implode('<br />', $user->getMessages());
                                } else {
                                    // remove signup session
                                    session_unset();
                                    session_regenerate_id(true);

                                    // values to be used in summary email
                                    $signup_data['total_charge'] = number_format($pricingHelper->getPricing('final'), 2);

                                    // send confirmation email
                                    $this->getDI()->getMail()->send($signup_data['todyl_email'], 'Todyl Protection Order Summary ', 'confirmation', $signup_data);

                                    // login the user
                                    $this->auth->check(array(
                                        'email'    => $signup_data['todyl_email'],
                                        'password' => $signup_data['todyl_password'],
                                    ));

                                    // save current time - for 10 minute check
                                    $this->session->set('registration_time', time());

                                    // go to thankyou page
                                    $response = new Response();
                                    $response->redirect('/thankyou');

                                    // go to dashboard
                                    // $response = new Response();
                                    // $response->redirect('/dashboard');

                                    return $response;
                                }
                            } else {
                                $signupMessage = 'Error while subscribing customer: ' . $this->session->get('stripe_error');
                                $this->session->remove('stripe_error');
                            }
                        }
                    }

                    // we've come this far - means there was an error
                    $this->session->set('signup_data', $signup_data);
                }
            } else {
                foreach ($messages as $message) {
                    $signupErrors[$message->getField()][] = $message->getMessage();
                }
            }

        } else {
            $helper->setStep($step);
        }

        // set helper properties
        $helper->setSavedData($signup_data);
        $helper->setErrors($signupErrors);

        $this->view->setVars(
            array(
                'current_step'          => $step,
                'stripe_pk'             => $stripe_pk,
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

    protected function handleAccounts($signup_data)
    {
        //-- organization --

        $orgData = [
            'name'      => $signup_data['todyl_business_name'],
            'devices'   => $signup_data['num_devices'],
            'created_by'=> 'thrust',
            'updated_by'=> 'thrust',
        ];

        if (isset($signup_data['orgId']) && $signup_data['orgId']) {
            // organization is already created - update it

            $organization = EntOrganization::findFirst([
                'conditions' => 'pk_id = ?1',
                'bind'       => [
                    1 => $signup_data['orgId'],
                ],
                'cache'      => false,
            ]);

            if ($organization->update($orgData) === false) {
                return [
                    'signupMessage' => 'Error while updating organization<br />' . implode('<br />', $organization->getMessages()),
                ];
            }
        } else {
            // create new organization

            $organization = new EntOrganization();
            if ($organization->create($orgData) === false) {
                return [
                    'signupMessage' => 'Error while creating organization<br />' . implode('<br />', $organization->getMessages()),
                ];
            }
        }

        //-- user --

        $role = AttrRoles::findFirst([
            'conditions' => 'name = ?1 AND is_active = ?2',
            'bind'       => [
                1 => 'admin',
                2 => 1,
            ],
        ]);

        // generate password
        $password = $this->getDI()->getSecurity()->hash($signup_data['todyl_password']);

        $userData = [
            'firstName'                 => $signup_data['todyl_first_name'],
            'lastName'                  => $signup_data['todyl_last_name'],
            'email'                     => $signup_data['todyl_email'],
            'password'                  => $password,
            'fk_ent_organization_id'    => $organization->pk_id,
            'fk_attr_roles_id'          => $role->pk_id,
            'is_active'                 => 0,
            'created_by'                => 'thrust',
            'updated_by'                => 'thrust',
        ];

        if (isset($signup_data['userId']) && $signup_data['userId']) {
            // update user

            $user = EntUsers::findFirst([
                'conditions' => 'pk_id = ?1',
                'bind'       => [
                    1 => $signup_data['userId'],
                ],
                'cache'      => false,
            ]);

            if ($user->update($userData) === false) {
                return [
                    'signupMessage' => 'Error while updating user<br />' . implode('<br />', $user->getMessages()),
                ];
            }
        } else {
            // create user

            $user = new EntUsers();
            if ($user->create($userData) === false) {
                return [
                    'signupMessage' => 'Error while creating user<br />' . implode('<br />', $user->getMessages()),
                ];
            }
        }

        return [
            'user'          => $user,
            'organization'  => $organization,
        ];
    }
}
