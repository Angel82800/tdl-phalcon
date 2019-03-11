<?php

namespace Thrust\Phalcon;

use Phalcon\Mvc\View\Engine\Volt as VoltEngine;

use Thrust\Acl\Acl;
// use Thrust\Auth\Auth;

// Helper class for functions
class Helpers
{
  public static function formatCurrency($amount)
  {
    return '$' . number_format($amount, 2);
  }

  public static function formatStripeCurrency($amount)
  {
    return '$' . number_format($amount / 100, 2);
  }

  public static function formatDateWithoutYear($timestamp)
  {
    return date('F j', $timestamp);
  }

}

class TVolt extends VoltEngine
{
  protected $thrust_config;

  function __construct($view, $di, $config)
  {
    $this->thrust_config = $config;

    parent::__construct($view, $di);
  }

  public function getCompiler()
  {
    $acl = new Acl();
    // $auth = new Auth();

    if (empty($this->_compiler))
    {
      parent::getCompiler();

      // add macros that need initialized before parse time
      $this->partial('macros/private');
    }

    $config = $this->thrust_config;
    $compiler = $this->_compiler;

    $this->_compiler->addFunction(
      'template_exists',
      function($resolvedArgs, $exprArgs) use($config, $compiler) {
        return "file_exists('{$this->thrust_config->application->viewsDir}' . {$this->_compiler->expression($exprArgs[0]['expr'])} . '.volt')";
      }
    );

    // get current user role
    // $this->_compiler->addFunction(
    //   'getUserRole',
    //   function($resolvedArgs, $exprArgs) use($auth) {
    //     return $auth->getIdentity() ? "'{$auth->getIdentity()['role']}'" : '\'\'';
    //   }
    // );

    // get ACL user role list
    $this->_compiler->addFunction(
      'getRoles',
      function($resolvedArgs, $exprArgs) use($acl) {
        return '[ \'' . implode('\', \'', $acl->getRoles()) . '\' ]';
      }
    );

    // meta description
    $this->_compiler->addFunction(
      'get_description',
      function($resolvedArgs, $exprArgs) {
        return '$this->tag->getDescription()';
      }
    );

    // meta keywords
    $this->_compiler->addFunction(
      'get_keywords',
      function($resolvedArgs, $exprArgs) {
        return '$this->tag->getKeywords()';
      }
    );

    // meta robot
    $this->_compiler->addFunction(
      'get_robots',
      function($resolvedArgs, $exprArgs) {
        return '$this->tag->getRobots()';
      }
    );

    // number_format for currencies
    $this->_compiler->addFilter(
      'formatCurrency',
      function($resolvedArgs, $exprArgs) {
        return 'Thrust\Phalcon\Helpers::formatCurrency(' . $resolvedArgs . ');';
      }
    );

    // number_format for currencies with numbers from stripe (multiplied by 100)
    $this->_compiler->addFilter(
      'formatStripeCurrency',
      function($resolvedArgs, $exprArgs) {
        return 'Thrust\Phalcon\Helpers::formatStripeCurrency(' . $resolvedArgs . ');';
      }
    );

    // format timestamps to dates without year - e.g. March 5
    $this->_compiler->addFilter(
      'formatDateWithoutYear',
      function($resolvedArgs, $exprArgs) {
        return 'Thrust\Phalcon\Helpers::formatDateWithoutYear(' . $resolvedArgs . ');';
      }
    );

    return parent::getCompiler();
  }
}
