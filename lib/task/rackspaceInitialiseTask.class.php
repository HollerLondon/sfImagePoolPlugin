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
The [imagepool:rackspace|INFO] task sets up Rackspace Cloud Files caching options.
Call it with:

  [php symfony imagepool:rackspace|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $connection = $databaseManager->getDatabase($options['connection'])->getConnection();

    // add your code here
    $cache_options = sfConfig::get('app_sf_image_pool_cache', array());
    
    $this->logSection('setup', 'Starting setup...');
    
    $cache_options['class'] = 'sfImagePoolRackspaceCloudFilesCache';
    
    // set up caching class
    if (!isset($cache_options['adapter_options'])) 
    {
      $cache_options['adapter_options'] = array();
    }
    
    if (!isset($cache_options['adapter_options']['username']) || empty($cache_options['adapter_options']['username']))
    {
      $cache_options['adapter_options']['username'] = $this->ask('What is your Rackspace Cloud username?');
    }
    
    if (!isset($cache_options['adapter_options']['api_key']) || empty($cache_options['adapter_options']['api_key']))
    {
      $cache_options['adapter_options']['api_key'] = $this->ask('What is your Rackspace Cloud API key?');
    }
    
    if (!isset($cache_options['adapter_options']['auth_host']) || empty($cache_options['adapter_options']['auth_host']))
    {
      $host_uk = $this->askConfirmation('Are you a UK Rackspace Cloud customer? (y/N)','QUESTION',false);
      $cache_options['adapter_options']['auth_host'] = ($host_uk ? 'UK' : 'US');
    }
    
    if (!isset($cache_options['adapter_options']['container']) || empty($cache_options['adapter_options']['container']))
    {
      $cache_options['adapter_options']['container'] = $this->ask('What is the name of the cloud file container you want to store images in? (Will be created if doesn\'t exist)');
    }
    
    if (!isset($cache_options['adapter_options']['container_uri']) || empty($cache_options['adapter_options']['container_uri']))
    {
      $container = sfImagePoolRackspaceCloudFilesCache::setup($cache_options);
      $cache_options['off_site_uri'] = $container->cdn_uri;
    }
    
    $file = sfConfig::get('sf_config_dir').'/app.yml';
    $config = file_exists($file) ? sfYaml::load($file) : array();

    $config['all']['sf_image_pool']['cache'] = $cache_options;

    file_put_contents($file, sfYaml::dump($config, 5));
    
    $this->logSection('setup', 'Your global app.yml file is now fully configured');
  }
}
