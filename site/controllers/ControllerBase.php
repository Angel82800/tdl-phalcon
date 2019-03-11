<?php

namespace Thrust\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Dispatcher;

use Thrust\Models\EntSettings;

// use Thrust\Hanzo\Client as HanzoClient;
use Thrust\Helpers\MailHelper;

/**
 * ControllerBase
 * This is the base controller for all controllers in the application.
 */
class ControllerBase extends Controller
{
    /**
     * site config
     */
    protected $config;

    /**
     * site settings
     */
    protected $settings;

    /**
     * mail helper
     * @var Thrust\Helpers\MailHelper
     */
    protected $mail;

    /**
     * phalcon logger
     */
    protected $logger;

    /**
     * Called on creation. Create some shared variables for any
     * controller that extends ControllerBase.
     */
    public function onConstruct()
    {
        // $this->hanzo = HanzoClient::getInstance();
        $this->mail = new MailHelper();
        $this->logger = \Phalcon\Di::getDefault()->getShared('logger');

        // Setup DI config
        $this->config = \Phalcon\Di::getDefault()->get('config');

        // get settings
        $settings = EntSettings::find([
            'is_active' => true,
            'cache'     => 60,
        ]);

        $this->settings = [];
        foreach ($settings as $setting) {
            $this->settings[$setting->key] = $setting->value;
        }

    }

    /**
     * Execute before the router so we can determine if this is a private controller and must be authenticated,
     * or a public controller that is open to all.
     *
     * @param Dispatcher $dispatcher
     *
     * @return bool
     */
    public function beforeExecuteRoute(Dispatcher $dispatcher)
    {
        $controllerName = $dispatcher->getControllerName();
        $actionName = $dispatcher->getActionName();

        //- set meta tags (title, meta description, meta keywords)
        $meta_tags = $this->config->meta_tags;

        $this->tag->setTitle($this->getMetaTag('title', $meta_tags, $controllerName, $actionName));
        $this->tag->setDescription($this->getMetaTag('description', $meta_tags, $controllerName, $actionName));
        $this->tag->setKeywords($this->getMetaTag('keywords', $meta_tags, $controllerName, $actionName));
        $this->tag->setRobots($this->getMetaTag('robots', $meta_tags, $controllerName, $actionName));

        // Get the current identity
        $identity = $this->auth->getIdentity();

        $this->view->logged_in = is_array($identity);

        // Only check permissions on private controllers
        if ($this->acl->isPrivate($controllerName)) {

            // If there is no identity available the user is redirected to index/index
            if (! is_array($identity)) {
                $this->flash->notice('Please log in with your account to continue.');

                // not logged in - show log in page and redirect after log in
                if (! $this->request->isAjax()) {
                    // prevent ajax handler urls to be saved as redirectURi
                    $this->session->set('requested_url', $this->router->getRewriteUri());
                }

                $dispatcher->forward(array(
                    'controller' => 'session',
                    'action'     => 'login'
                ));

                return false;
            } else if (! in_array($controllerName, [ 'account', 'pricing', 'support' ]) && ! $identity['is_active']) {
                // suspended account trying to enter other pages

                $dispatcher->forward(array(
                    'controller' => 'account',
                    'action'     => 'index'
                ));

                return false;
            }

            // user info - access user info with identity[{param}]
            $this->view->identity = $identity;

            // private navigation
            $this->view->navigation_items = $this->config->private_navigation;

            // set user initials for top navigation link
            $this->view->user_initials = substr($identity['firstName'], 0, 1) . substr($identity['lastName'], 0, 1);

            $this->view->is_admin = ($identity['orgId'] == 1);

            //- Check if the user have permission to the current option

            // Get Action type (used for ajax handlers)
            $typeName = $this->request->getPost('type');

            if (! $this->acl->isAllowed($identity['role'], $controllerName, $actionName, $typeName)) {
                $this->logger->info('[CBASE] User ID ' . $identity['id'] . ' tried to access restricted action : ' . $controllerName . '->' . $actionName . ($typeName ? '->' . $typeName : ''));

                if ($typeName) {
                    // this was an ajax call - return json data
                    $response = new \Phalcon\Http\Response();
                    $response->setStatusCode(400, 'You are not allowed to do this.');

                    $response->setContent(json_encode($content));
                    $response->send();
                    exit;
                } else {
                    $this->flash->notice('Sorry, we could not find the page you\'re looking for.');

                    if ($this->acl->isAllowed($identity['role'], $controllerName, 'index')) {
                        $dispatcher->forward(array(
                            'controller' => $controllerName,
                            'action'     => 'index'
                        ));
                    } else {
                        $dispatcher->forward(array(
                            'controller' => 'dashboard',
                            'action'     => 'index'
                        ));
                    }

                    return false;
                }
            }
        }
    }

    public function afterExecuteRoute(Dispatcher $dispatcher)
    {
        // unset($this->hanzo);
    }

    public function redirectIfAuthenticated($controller)
    {
        $identity = $this->auth->getIdentity();

        // If user is already logged in redirect them to controller
        if (is_array($identity)) {
            return $this->response->redirect($controller);
        }

        return false;
    }

    protected function getMetaTag($tag_name, $meta_tags, $controller_name, $action_name)
    {
        if (isset($meta_tags[$tag_name][$controller_name])) {
            if (! is_object($meta_tags[$tag_name][$controller_name])) {
                return $meta_tags[$tag_name][$controller_name];
            } else if (isset($meta_tags[$tag_name][$controller_name][$action_name])) {
                return $meta_tags[$tag_name][$controller_name][$action_name];
            } else if (isset($meta_tags[$tag_name][$controller_name]['default'])) {
                return $meta_tags[$tag_name][$controller_name]['default'];
            } else {
                return $meta_tags[$tag_name]['default'];
            }
        } else {
            return $meta_tags[$tag_name]['default'];
        }
    }

}
