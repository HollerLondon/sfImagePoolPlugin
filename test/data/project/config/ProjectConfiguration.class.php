<?php

require_once dirname(__FILE__).'/../../../../lib/vendor/symfony/lib/autoload/sfCoreAutoload.class.php';
sfCoreAutoload::register();

// To work within testing framework
class TestProjectConfiguration extends sfProjectConfiguration
{
  public function setup()
  {
    $this->enablePlugins('sfDoctrinePlugin', 'sfImagePoolPlugin');
    
    $this->setPluginPath('sfImagePoolPlugin', dirname(__FILE__).'/../../../..');
  }
}

// To work with test project on command line
if (!class_exists('ProjectConfiguration'))
{
  class ProjectConfiguration extends TestProjectConfiguration 
  {
  }
}