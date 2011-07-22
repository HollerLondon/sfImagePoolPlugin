<?php

// Only works with test
require_once $_SERVER['SYMFONY'].'/autoload/sfCoreAutoload.class.php';
sfCoreAutoload::register();

class TestProjectConfiguration extends sfProjectConfiguration
{
  public function setup()
  {
    $this->enablePlugins('sfDoctrinePlugin', 'sfImagePoolPlugin');
    
    $this->setPluginPath('sfImagePoolPlugin', dirname(__FILE__).'/../../../..');
  }
}
