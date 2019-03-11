<?php

namespace Thrust\Controllers;

use Phalcon\Http\Response;

use Thrust\Forms\LoginForm;
use Thrust\Forms\ForgotPasswordForm;
use Thrust\Forms\InviteUserForm;
use Thrust\Forms\ResetPasswordForm;

use Thrust\Models\EntAgent;
use Thrust\Models\EntUsers;
use Thrust\Models\AttrPasswordChangeType;
use Thrust\Models\LogPasswordChanges;
use Thrust\Models\AttrRoles;

use Thrust\Auth\Exception as AuthException;

use Thrust\Helpers\AccountHelper;

/**
 * Controller used handle non-authenticated session actions like login/logout, and forgotten passwords.
 */
class SessionController extends ControllerBase
{
    public function indexAction()
    {
    }

    /**
     * Starts a session in the admin backend.
     */
    public function loginAction()
    {
        // If user is already logged in redirect them to dashboard
        $this->redirectIfAuthenticated('dashboard');

        $form = new LoginForm();
        $loginMessage = null;
        $errorCode = 0;

        try {
            if (!$this->request->isPost()) {
                if ($this->auth->hasRememberMe()) {
                    return $this->auth->loginWithRememberMe();
                }
            } else {
                if ($form->isValid($this->request->getPost()) === false) {
                    foreach ($form->getMessages() as $message) {
                        $loginMessage .= $message . '<br/>';
                    }
                } else {
                    $this->auth->check(array(
                        'email'    => $this->request->getPost('todyl_email'),
                        'password' => $this->request->getPost('todyl_password'),
                        'remember' => $this->request->getPost('remember')
                    ));

                    $original_url = $this->session->get('requested_url');

                    if ($original_url) {
                        $this->session->remove('requested_url');
                        return $this->response->redirect($original_url, true);
                    } else {
                        return $this->response->redirect('dashboard');
                    }
                }
            }
        } catch (AuthException $e) {
            if ($e->getMessage() == 'password_reset') {
                $this->flashSession->warning('You recently tried to update your password, please check your email for the password reset link. If you did not receive this email check your spam or junk folder.');
                return $this->response->redirect('/session/forgotPassword', true);
            } else {
                $errorCode = $e->getCode();
                $loginMessage = $e->getMessage();
            }
        }

        if ($errorCode == 999) {
            $this->flashSession->warning($loginMessage);
        } else {
            $this->flashSession->error($loginMessage);
        }

        $this->view->setVars(
            array(
                'form'         => $form,
                // 'loginMessage' => $loginMessage
            )
        );
    }

    /**
     * Closes the session.
     */
    public function logoutAction()
    {
        $this->auth->remove();

        return $this->response->redirect('/');
    }

    /**
     * Shows the forgot password form.
     */
    public function forgotPasswordAction()
    {
        $form = new ForgotPasswordForm();

        if ($this->request->isPost()) {
            if ($form->isValid($this->request->getPost()) == false) {
                foreach ($form->getMessages() as $message) {
                    $this->flashSession->error($message);
                }
            } else {
                $account = new AccountHelper();

                $result = $account->triggerForgotPassword($this->request->getPost('todyl_email'));

                if ($result['result'] == 'success') {
                    // revert back layout after sending email
                    $this->view->setLayout('');
                    $this->view->pick('session/forgotSuccess');
                } else if ($result['result'] == 'error') {
                    $this->logger->error('[SESSION] Error : ' . $result['error']);
                    $this->flashSession->error('Sorry, there was an error while processing your request.');
                } else if ($result['result'] == 'not_found') {
                    $this->view->pick('session/forgotSuccess');
                }
            }
        }

        $this->view->form = $form;
    }

    /**
     * handles the reset password
     */
    public function resetPasswordAction()
    {
        $form = new ResetPasswordForm();

        $code = $this->dispatcher->getParam('code');

        if ($code) {
            if ($this->request->isPost()) {
                if ($form->isValid($this->request->getPost()) == false) {
                    foreach ($form->getMessages() as $message) {
                        $this->flashSession->error($message);
                    }
                } else {
                    $account = new AccountHelper();

                    $result = $account->resetUserPassword($this->request->getPost('todyl_email'));

                    if ($result['result'] == 'success') {
                        $this->flashSession->success('Your password has successfully been reset. Please log in with your new password.');
                        return $this->response->redirect('/session/login');
                    } else if ($result['result'] == 'error') {
                        $this->logger->error('[SESSION] Error : ' . $result['error']);
                        $this->flashSession->error('Sorry, there was an error while processing your request.');
                    } else if ($result['result'] == 'not_found') {
                        $this->logger->error('[SESSION] Reset password user not found with token `' . $code . '`');
                        $this->flashSession->success('Sorry, we couldn\'t process your request. If you continue to have this problem, please contact us.');
                    }
                }
            }

            $user = EntUsers::findFirst([
                'conditions' => 'token_GUID = ?1 AND token_type = ?2 AND is_active = 1 AND is_deleted = 0',
                'bind'       => [
                    1 => $code,
                    2 => 'password_reset',
                ],
                'cache'      => false,
            ]);

            // if ($user && $user->organization->is_active) {
            if ($user) {
                // check one hour validation & if the link was already used
                if (strtotime($user->token_time) < time() && strtotime($user->token_time) > (time() - 3600) && $user->token_type == 'password_reset') {
                    // all tests passed!

                    $this->view->user = $user;
                    $this->view->form = $form;
                } else {
                    // 1 hour expired
                    $this->flashSession->error('Sorry, the link is not valid anymore. Please submit a new request.');
                    return $this->response->redirect('/session/forgotPassword');
                }
            } else {
                $this->logger->error("Reset password page redirecting to login.\nReason: No user found with GUID $code");
                return $this->response->redirect('/session/login');
            }
        } else {
            return $this->response->redirect('/session/login');
        }
    }

    /**
     * handles user invitation
     */
    public function inviteAction()
    {
        $form = new InviteUserForm();

        $download_GUID = $this->session->get('invite_download_GUID');

        if (! $download_GUID) {
            return $this->response->redirect('/session/login');
        }

        $agent = EntAgent::findFirst([
            'conditions' => 'download_GUID = ?1 AND is_active = 1 AND is_deleted = 0',
            'bind'       => [
                1 => $download_GUID,
            ],
            'cache'      => false,
        ]);

        if (! $agent) {
            $this->logger->error('[SESSION] Device for the invitation GUID not found.');

            $this->flashSession->error('Sorry, the link is not valid anymore. Please contact the individual who invited you.');
            return $this->response->redirect('/session/login');
        }

        if ($this->request->isPost()) {
            if ($form->isValid($this->request->getPost()) == false) {
                foreach ($form->getMessages() as $message) {
                    $this->flashSession->error($message);
                }
            } else {
                $account = new AccountHelper();

                $result = $account->resetUserPassword($this->request->getPost('todyl_email'), true);

                if ($result['result'] == 'success') {
                    try {
                        $this->auth->check([
                            'email'    => $this->request->getPost('todyl_email'),
                            'password' => $this->request->getPost('todyl_password'),
                        ], false);
                    } catch (\Exception $e) {
                        $this->logger->error('[SESSION] Error while logging in the invited user : ' . $e->getMessage());
                    }

                    $this->session->remove('invite_download_GUID');

                    $this->logger->info('[SESSION] Successfully created password for user ID ' . $result['user']->pk_id);

                    return $this->response->redirect('/dnld/' . $download_GUID);
                } else if ($result['result'] == 'error') {
                    $this->logger->error('[SESSION] Error : ' . $result['error']);
                    $this->flashSession->error('Sorry, there was an error while processing your request.');
                    return $this->response->redirect('/session/login');
                } else if ($result['result'] == 'not_found') {
                    $this->flashSession->error('Sorry, we couldn\'t process your request. Please try again.');
                    return $this->response->redirect('/session/login');
                }
            }
        }

        $agent_user = $agent->getUser([ 'cache' => false ]);

        if ($agent_user && $agent_user->organization->is_active) {
            // check one hour validation & if the link was already used
            if (strtotime($agent_user->token_time) < time() && strtotime($agent_user->token_time) > (time() - 3600)) {
                // all tests passed!

                // get org admin info
                $role = AttrRoles::findFirst([
                    'conditions' => 'name = ?1 AND is_active = ?2',
                    'bind'       => [
                        1 => 'admin',
                        2 => 1,
                    ],
                ]);

                $org_admin = EntUsers::findFirst([
                    'conditions' => 'fk_attr_roles_id = ?1 AND is_active = 1 AND is_deleted = 0',
                    'bind'       => [
                        1 => $role->pk_id,
                    ],
                    'cache'      => false,
                ]);

                $org_name = $org_admin->organization->name ? $org_admin->organization->name :
                    ($org_admin->firstName ? $org_admin->firstName : $org_admin->email);

                $data = [
                    'org_name'      => $org_name,
                    'org_admin'     => $org_admin,
                    'user'          => $agent_user,
                    'form'          => $form,
                ];

                $this->view->setVars($data);
            } else {
                // 1 hour expired
                $this->logger->error('[SESSION] Invitation link expired');

                $this->flashSession->error('Sorry, the link is not valid anymore. Please contact the individual who invited you.');
                return $this->response->redirect('/session/login');
            }
        } else {
            $this->logger->error("Invitation page redirecting to login.\nReason: No user found with specified download GUID $download_GUID");
            return $this->response->redirect('/session/login');
        }
    }

    /**
     * handle exceptions
     * @param  [Exception] $e [Exception thrown from Session controller]
     */
    public function errorAction($e)
    {
        $exceptionClass = get_class($e);
        $message = $e->getMessage();

        $this->logger->error('Session Exception: ' . $e->getMessage() . ' with trace: ' . (string) $e->getTraceAsString());
        echo "Exception caught by session : " . $message;
        return;
    }

}
