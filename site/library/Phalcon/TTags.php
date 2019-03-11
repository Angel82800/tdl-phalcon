<?php

namespace Thrust\Phalcon;

use Phalcon\Tag as Tag;

class TTags extends Tag {

  protected $description = '';
  protected $keywords = '';
  protected $robots = '';

  /**
   * get meta description
   * @return string - meta description html
   */
  public function getDescription()
  {
    return '<meta name="description" content="' . $this->description . '">' . "\n";
  }

  /**
   * set meta description
   */
  public function setDescription($description)
  {
    $this->description = $description;
  }

  /**
   * get meta keywords
   * @return string - meta keywords html
   */
  public function getKeywords()
  {
    return '<meta name="keywords" content="' . $this->keywords . '">' . "\n";
  }

  /**
   * set meta keywords
   */
  public function setKeywords($keywords)
  {
    $this->keywords = $keywords;
  }

  /**
   * get meta robots
   * @return string - meta robots html
   */
  public function getRobots()
  {
    return '<meta name="robots" content="' . $this->robots . '">' . "\n";
  }

  /**
   * set meta robots
   */
  public function setRobots($robots)
  {
    $this->robots = $robots;
  }

}
