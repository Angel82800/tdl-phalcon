<?php

namespace Thrust\Auth;

use Phalcon\Mvc\User\Component;

use Thrust\Models\AttrRoles;
use Thrust\Models\EntUsers;
use Thrust\Models\RememberTokens;
use Thrust\Models\LogLogins;
use Thrust\Models\LogAnonLoginAttempt;
use Thrust\Hanzo\Client as HanzoClient;

use Thrust\Stripe\Api as Stripe;

use Thrust\Models\DashboardStatistics;
use Thrust\Helpers\FtuHelper;

/**
 * Thrust\Auth\Auth
 * Manages Authentication/Identity Management in Thrust.
 */
class Auth extends Component
{
    protected $logger;

    function __construct()
    {
        $this->logger = \Phalcon\Di::getDefault()->getShared('logger');
    }

    /**
     * Checks the user credentials.
     *
     * @param array $credentials
     *
     * @return boolean
     */
    public function check($credentials, $check_flags = true)
    {
        // Check if the user exists
        // $user = EntUsers::findFirstByEmail($credentials['email']);
        $user = EntUsers::findFirst([
            'conditions'    => 'email = ?1 AND is_deleted = 0',
            'bind'          => [
                1 => $credentials['email'],
            ],
            'cache' => false,
        ]);

        if ($user) {
            $this->logger->info('[AUTH] User : ' . print_r($user->toArray(), true));
        }

        if (! $user) {
            $this->unknownUserThrottling();
            $this->logger->error('[AUTH] User not found');
            throw new Exception("We're sorry, we couldn't find that email address or password. Please try again.");
        }

        // Check the password
        if (! $this->security->checkHash($credentials['password'], $user->password)) {
            $this->registeredUserThrottling($user->pk_id);
            $this->logger->error('[AUTH] Password doesn\'t match.');
            throw new Exception("We're sorry, we couldn't find that email address or password. Please try again.");
        }

        // user exists
        $organization = $user->getOrganization([ 'cache' => false ]);

        // Check if organization is active
        if (! $organization->is_active) {
            $role = AttrRoles::findFirst([
                'conditions' => 'name = ?1 AND is_active = 1 AND is_deleted = 0',
                'bind'       => [
                    1 => 'admin',      // TODO - update this accordingly
                ],
            ]);

            $this->logger->error('[AUTH] Organization is suspended');

            if ($user->fk_attr_roles_id != $role->pk_id) {
                // user is not an admin and can't reactivate the organization
                throw new Exception('<h4>Todyl Protection is no longer available for this organization.</h4><p>Your administrator has suspended service for all devices associated with this account. If service is reactivated, you will be notified via email. You may also sign up for Todyl Protection on your own, by clicking the "Sign Up" link below.</p>', 999)
                ;
            }
        }

        if ($check_flags) {
            // Check if the user was flagged
            $this->checkUserFlags($user);
        }

        // Check if user has requested reset password
        if ($user->token_type == 'password_reset') {
            throw new Exception('password_reset');
        }

        // Register the successful login
        $this->saveSuccessLogin($user);

        // Check if the remember me was selected
        if (isset($credentials['remember'])) {
            $this->createRememberEnviroment($user);
        }

        $this->setIdentity($user);

        $stat = new DashboardStatistics();
        $ftu = new FtuHelper();

        // get user device count
        $this->session->set('is_ftu', $stat->isFtu($user->pk_id));
        // get user FTU history
        $this->session->set('ftu', $ftu->getFtuHistory($user->pk_id));
    }

    /**
     * Creates the remember me environment settings the related cookies and generating tokens.
     *
     * @param Thrust\Models\EntUsers $user
     */
    public function saveSuccessLogin($user)
    {
        $successLogin = new LogLogins();
        $successLogin->fk_ent_users_id = $user->pk_id;
        $successLogin->ip_geolocation = $this->getGeoIp();
        $successLogin->ip_address = $this->request->getClientAddress();
        $successLogin->user_agent = $this->request->getUserAgent();
        $successLogin->is_failed = 0;
        $successLogin->created_by = 'Thrust';
        $successLogin->updated_by = 'Thrust';
        if (!$successLogin->save()) {
            $messages = $successLogin->getMessages();
            throw new Exception($messages[0]);
        }
    }

    /**
     * Implements login logging for unknwon users.
     */
    public function unknownUserThrottling()
    {
        $failedLogin = new LogAnonLoginAttempt();
        $failedLogin->ip_geolocation = $this->getGeoIp();
        $failedLogin->ip_address = $this->request->getClientAddress();
        $failedLogin->user_agent = $this->request->getUserAgent();
        $failedLogin->created_by = 'Thrust';
        $failedLogin->updated_by = 'Thrust';
        $failedLogin->save();

        $attempts = LogAnonLoginAttempt::count(array(
            'ip_address = ?0 AND datetime_created >= ?1',
            'bind' => array(
                $this->request->getClientAddress(),
                time() - 3600 * 6
            )
        ));

		$this->throttle($attempts);
    }

   /**
     * Logs attempts to access a known/active user account.
     *
     * @param int $userId
     */
    public function registeredUserThrottling($userId)
    {
        $failedLogin = new LogLogins();
        $failedLogin->fk_ent_users_id = $userId;
        $failedLogin->ip_geolocation = $this->getGeoIp();
        $failedLogin->ip_address = $this->request->getClientAddress();
        $failedLogin->user_agent = $this->request->getUserAgent();
        $failedLogin->is_failed = 1;
        $failedLogin->created_by = 'Thrust';
        $failedLogin->updated_by = 'Thrust';
        $failedLogin->save();

        $attempts = LogLogins::count(array(
            'ip_address = ?0 AND is_failed = 1 AND datetime_created >= ?1',
            'bind' => array(
                $this->request->getClientAddress(),
                time() - 3600 * 6
            )
        ));

		$this->throttle($attempts);
    }

    /**
     * Creates the remember me environment settings the related cookies and generating tokens.
     *
     * @param Thrust\Models\EntUsers $user
     */
    public function createRememberEnviroment(EntUsers $user)
    {
        $userAgent = $this->request->getUserAgent();
        $token = md5($user->email . $user->password . $userAgent);

        $remember = new RememberTokens();
        $remember->usersId = $user->id;
        $remember->token = $token;
        $remember->userAgent = $userAgent;

        if ($remember->save() != false) {
            $expire = time() + 86400 * 8;
            $this->cookies->set('RMU', $user->id, $expire);
            $this->cookies->set('RMT', $token, $expire);
        }
    }

    /**
     * Check if the session has a remember me cookie.
     *
     * @return bool
     */
    public function hasRememberMe()
    {
        return $this->cookies->has('RMU');
    }

    /**
     * Logs on using the information in the coookies.
     *
     * @return Phalcon\Http\Response
     */
    public function loginWithRememberMe()
    {
        $userId = $this->cookies->get('RMU')->getValue();
        $cookieToken = $this->cookies->get('RMT')->getValue();

        $user = EntUsers::findFirst([
            'conditions' => 'pk_id = ?1',
            'bind'       => [
                1 => $userId,
            ],
            'cache'      => false,
        ]);
        if ($user) {
            $userAgent = $this->request->getUserAgent();
            $token = md5($user->email . $user->password . $userAgent);

            if ($cookieToken == $token) {
                $remember = RememberTokens::findFirst(array(
                    'usersId = ?0 AND token = ?1',
                    'bind' => array(
                        $user->id,
                        $token
                    )
                ));
                if ($remember) {

                    // Check if the cookie has not expired
                    if ((time() - (86400 * 8)) < $remember->createdAt) {

                        // Check if the user was flagged
                        $this->checkUserFlags($user);

                        // Register identity
                        $this->setIdentity($user);

                        // Register the successful login
                        $this->saveSuccessLogin($user);

                        return $this->response->redirect('users');
                    }
                }
            }
        }

        $this->cookies->get('RMU')->delete();
        $this->cookies->get('RMT')->delete();

        return $this->response->redirect('session/login');
    }

    /**
     * Checks if the user is banned/inactive/suspended.
     *
     * @param Thrust\Models\EntUsers $user
     */
    public function checkUserFlags(EntUsers $user)
    {
        if ($user->is_active != 1) {
            throw new Exception('The user is inactive');
        }

        if ($user->is_banned == 1) {
            throw new Exception('The user is banned');
        }

        // if ($user->is_suspended == 1) {
        //     throw new Exception('The user is suspended');
        // }

        if ($user->is_deleted == 1) {
            throw new Exception('The user is deleted');
        }
    }

    /**
     * Returns the current identity.
     *
     * @return array
     */
    public function getIdentity()
    {
        return $this->session->get('auth-identity');
    }

    /**
     * Update user identity object
     *
     * @return array
     */
    public function updateIdentity()
    {
        $identity = $this->session->get('auth-identity');

        $user = EntUsers::findFirst([
            'conditions' => 'pk_id = ?1',
            'bind'       => [
                1 => $identity['id'],
            ],
            'cache'      => false,
        ]);

        $this->setIdentity($user);

        $newIdentity = $this->session->get('auth-identity');

        $this->logger->info('[AUTH] Updated identity object : ' . print_r($newIdentity, true));

        return $newIdentity;
    }

    /**
     * Removes the user identity information from session.
     */
    public function remove()
    {
        if ($this->cookies->has('RMU')) {
            $this->cookies->get('RMU')->delete();
        }
        if ($this->cookies->has('RMT')) {
            $this->cookies->get('RMT')->delete();
        }

        // $this->session->remove('auth-identity');

        session_unset();
        session_regenerate_id(true);
    }

    /**
     * Auths the user by his/her id.
     *
     * @param int $id
     */
    public function authUserById($id)
    {
        $user = EntUsers::findFirst([
            'conditions' => 'pk_id = ?1',
            'bind'       => [
                1 => $id,
            ],
            'cache'      => false,
        ]);

        if ($user == false) {
            throw new Exception('The user does not exist');
        }

        $this->checkUserFlags($user);

        $this->setIdentity($user);
    }

    /**
     * Get the entity related to user in the active identity.
     *
     * @return \Thrust\Models\EntUsers
     */
    public function getUser()
    {
        $identity = $this->session->get('auth-identity');
        if (isset($identity['id'])) {
            $user = EntUsers::findFirst([
                'conditions' => 'pk_id = ?1',
                'bind'       => [
                    1 => $identity['id'],
                ],
                'cache'      => false,
            ]);

            if ($user == false) {
                throw new Exception('The user does not exist');
            }

            return $user;
        }

        return false;
    }

   /**
     * Grab the IP Geolocation from CloudFlare
     */
	public function getGeoIp()
	{
		return ($this->request->getHeader("HTTP_CF_IPCOUNTRY")) ? $this->request->getHeader("HTTP_CF_IPCOUNTRY") : "XX";
	}

   /**
     * Implements login throttling
     * Reduces the efectiveness of brute force attacks.
     *
     * @param int $attempts
     */

	private function throttle($attempts)
	{
		switch ($attempts) {
            case 1:
            case 2:
                // no delay
                break;
            case 3:
            case 4:
                sleep(2);
                break;
            default:
                sleep(4);
                break;
        }
	}

    /**
     * set user identity in session
     * @param array $user - EntUsers object
     */
    private function setIdentity($user)
    {
        // user exists
        $organization = $user->getOrganization([ 'cache' => false ]);

        $this->session->set('auth-identity', array(
            'id'                => $user->pk_id,
            'GUID'              => $user->GUID,
            'firstName'         => $user->firstName,
            'lastName'          => $user->lastName,
            'email'             => $user->email,
            'orgId'             => $user->fk_ent_organization_id,
            'phone'             => $user->primaryPhone,
            'is_beta'           => $user->is_beta,
            'is_active'         => $organization->is_active,
            'email_verified'    => $user->email_verified,
            'role'              => $user->role->name, // admin - Admin, user - Normal User
            'is_professional'   => $user->is_professional,
        ));
    }

}
