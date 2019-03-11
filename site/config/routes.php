<?php
/*
 * Define custom routes. File gets included in the router service definition.
 */
$router = new Phalcon\Mvc\Router();

// stripe webhook
$router->add('/webhook', array(
    'controller' => 'common',
    'action'     => 'stripeWebhook'
));

// reset password email link
$router->add('/reset-password/{code}', array(
    'controller' => 'session',
    'action'     => 'resetPassword'
));

// user invitation link
$router->add('/invitation/{code}', array(
    'controller' => 'session',
    'action'     => 'invite'
));

// user email verification link
$router->add('/verify/{code}', array(
    'controller' => 'common',
    'action'     => 'confirmEmailVerification'
));

// send installation instructions
$router->add('/send-instructions', [
    'controller'    => 'common',
    'action'        => 'sendInstructions'
]);

// user sign up first page
$router->add('/signup', [
    'controller'	=> 'registration',
    'action'		=> 'index'
]);

// user sign up multi step form routing
$router->add('/signup/{step}', [
    'controller'	=> 'registration',
    'action'		=> 'index'
]);

// thank you page
$router->add('/thankyou', [
    'controller'    => 'index',
    'action'        => 'thankyou'
]);

// privacy policy page
$router->add('/privacy', [
    'controller'    => 'index',
    'action'        => 'privacy'
]);

// terms page
$router->add('/terms', [
    'controller'    => 'index',
    'action'        => 'terms'
]);

// customer agreement page
$router->add('/customeragreement', [
    'controller'    => 'index',
    'action'        => 'customeragreement'
]);

// beta terms page
$router->add('/terms/beta', [
    'controller'	=> 'index',
    'action'		=> 'betaterms'
]);

// deactivate device
$router->add('/device/([a-zA-Z0-9_-]+)/([0-9]+)', array(
	'controller'	=> 'device',
	'action'		=> 'management',
    'type'          => 1,
    'device_id'     => 2,
));

// download via magic link
$router->add('/dnld/{code}', array(
    'controller' => 'download',
    'action'     => 'magicDownload'
));

//--- support route

// view topic
$router->add('/support/topic/([0-9]+)', array(
    'controller'    => 'support',
    'action'        => 'viewTopic',
    'topic'         => 1,
));

// add article
$router->add('/support/add/([0-9]+)', array(
    'controller'    => 'support',
    'action'        => 'editArticle',
    'topic'         => 1,
));

// edit article
$router->add('/support/edit/([0-9]+)', array(
    'controller'    => 'support',
    'action'        => 'editArticle',
    'article'       => 1,
));

// view article
$router->add('/support/view/([0-9]+)', array(
    'controller'    => 'support',
    'action'        => 'viewArticle',
    'article'       => 1,
));

// contenttools
$router->add('/support/contenttools/{event}', array(
    'controller'    => 'support',
    'action'        => 'contenttools',
));

//--- alert review tools

// view incident
$router->add('/review/incident/([0-9]+)', array(
    'controller'    => 'review',
    'action'        => 'viewIncident',
    'incident'      => 1,
));

return $router;
