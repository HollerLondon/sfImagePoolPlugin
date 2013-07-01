<?php

class rackspaceInitialiseTask extends sfBaseTask
{
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'backend'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'doctrine'),
    ));

    $this->namespace        = 'rackspace';
    $this->name             = 'initialise';
    $this->briefDescription = 'Sets up Rackspace Cloud Files caching';
    $this->detailedDescription = <<<EOF
The [rackspace:initialise|INFO] task sets up Rackspace Cloud Files caching options.
Call it with:

  [php symfony rackspace:initialise|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $connection = $databaseManager->getDatabase($options['connection'])->getConnection();

    // Get config
    $cache_options = sfConfig::get('app_sf_image_pool_cache', array());
    
    // If not loaded
    if (!isset($cache_options['class']) || 'sfImagePoolRackspaceCloudFilesCache' != $cache_options['class']) // check for rackspace cache
    {
      require_once sfConfig::get('sf_lib_dir') . '/vendor/rackspace/lib/php-opencloud.php';
      //$classLoader = new SplClassLoader('OpenCloud', sfConfig::get('sf_lib_dir') . '/vendor/rackspace/lib');
      //$classLoader->register();
    }
    
    $this->logSection('setup', 'Starting setup...');
    $cache_options['class'] = 'sfImagePoolRackspaceCloudFilesCache';
    
    // set up caching class
    if (!isset($cache_options['options'])) 
    {
      $cache_options['options'] = array();
    }
    
    if (!isset($cache_options['options']['username']) || empty($cache_options['options']['username']))
    {
      $cache_options['options']['username'] = $this->ask('What is your Rackspace Cloud username?');
    }
    
    if (!isset($cache_options['options']['api_key']) || empty($cache_options['options']['api_key']))
    {
      $cache_options['options']['api_key'] = $this->ask('What is your Rackspace Cloud API key?');
    }
    
    if (!isset($cache_options['options']['auth_host']) || empty($cache_options['options']['auth_host']))
    {
      $cache_options['options']['auth_host'] = strtoupper($this->ask('What is your Rackspace Cloud region? Enter the 3 digit code:  Dallas/Fort Worth (DFW), Chicago (ORD), or London (LON)?'));
    }
    
    if (!isset($cache_options['options']['container']) || empty($cache_options['options']['container']))
    {
      $cache_options['options']['container'] = $this->ask('What is the name of the cloud file container you want to store images in? (Will be created if doesn\'t exist)');
    }
    
    if (!isset($cache_options['options']['container_uri']) || empty($cache_options['options']['container_uri']))
    {
      $container = sfImagePoolRackspaceCloudFilesCache::setup($cache_options);
      
      $cache_options['off_site_uri']     = $container->CDNURI();
      $cache_options['off_site_ssl_uri'] = $container->SSLURI();
    }
    
    $file = sfConfig::get('sf_config_dir').'/app.yml';
    $config = file_exists($file) ? sfYaml::load($file) : array();

    $config['all']['sf_image_pool']['cache'] = $cache_options;

    $this->logSection('setup', 'Your configuration is as follows:');
    
    echo sfYaml::dump($config, 5);
    
    if ($this->askConfirmation('Do you want to write that to your app.yml?'))
    {
      file_put_contents($file, sfYaml::dump($config, 5));
      $this->logSection('setup', 'Your global app.yml file is now fully configured');
    }
    else $this->logSection('setup', 'Thank you');
  }
}
