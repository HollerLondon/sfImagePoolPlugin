<?php

class sfPluginTestBootstrap
{
  protected
    $symfonyDir     = null,
    $configuration  = null,
    $context        = null;

  public function __construct($symfonyDir)
  {
    $this->setSymfonyDir($symfonyDir);
    $this->setSymfonyPluginsDir(dirname(__FILE__).'/../../..');
  }
  
  public function teardown()
  {
    sfToolkit::clearDirectory(dirname(__FILE__).'/../data/project/cache');
    sfToolkit::clearDirectory(dirname(__FILE__).'/../data/project/log');
  }
  
  public function setup()
  {
    // Copy over test SQLite database
    copy(dirname(__FILE__).'/../data/project/data/clean_database.sqlite', dirname(__FILE__).'/../data/project/data/database.sqlite');
  }
  
  public function autoload($class)
  {
    $autoload = sfSimpleAutoload::getInstance();
    $autoload->reload();
    return $autoload->autoload($class);
  }
  
  public function getConfiguration()
  {
    return $this->configuration;
  }
  
  public function getContext()
  {
    return $this->context;
  }
  
  public function setSymfonyDir($symfonyDir)
  {
    $this->symfonyDir   = $symfonyDir;
    $_SERVER['SYMFONY'] = $symfonyDir;
  }
  
  public function setSymfonyPluginsDir($pluginsDir)
  {
    $this->pluginsDir               = $pluginsDir;
    $_SERVER['SYMFONY_PLUGINS_DIR'] = $pluginsDir;
  }
  
  public function getSymfonyDir()
  {
    return $this->symfonyDir;
  }
  
  public function bootstrap($app = 'backend', $debug = true)
  {
    // so that all notices will appear
    error_reporting(E_ALL);
    
    // Load symfony core and lime testing framework
    // Create configuration and context
    require_once dirname(__FILE__).'/../data/project/config/ProjectConfiguration.class.php';
    // Ensure root dir is set as test project.
    $this->configuration = TestProjectConfiguration::getApplicationConfiguration($app, 'test', $debug, dirname(__FILE__).'/../data/project');
    
    require_once $this->configuration->getSymfonyLibDir().'/vendor/lime/lime.php';
    $this->context = sfContext::createInstance($this->configuration);
    
    new sfDatabaseManager($this->configuration);
    
    // Register teardown / autoload functions
    spl_autoload_register(array($this, 'autoload'));
    register_shutdown_function(array($this, 'teardown'));

    // Cleanup and copy over SQLite DB
    $this->teardown();
    $this->setup();
  }
}