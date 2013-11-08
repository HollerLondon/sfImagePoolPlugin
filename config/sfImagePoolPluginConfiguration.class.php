<?php
class sfImagePoolPluginConfiguration extends sfPluginConfiguration
{
  static $base_dir_name;

  /**
   * Initialize plugin
   */
  public function initialize()
  {
    /*$this->dispatcher->connect('routing.load_configuration', array(
     'sfImagePoolRouting' , 'listenToRoutingLoadConfigurationEvent'
     ));*/

    self::$base_dir_name = sfConfig::get('app_sf_image_pool_folder');

    if (!file_exists(self::getBaseDir()))
    {
      mkdir(self::getBaseDir(), 0777);
    }

    $this->connectTests();
    
    // http://php-opencloud.com/#Installation
    $options  = sfConfig::get('app_sf_image_pool_cache', array());
    
    if (isset($options['class']) && 'sfImagePoolRackspaceCloudFilesCache' == $options['class']) // check for rackspace cache
    {
      require_once sfConfig::get('sf_lib_dir') . '/vendor/rackspace/lib/php-opencloud.php';
      //$classLoader = new SplClassLoader('OpenCloud', sfConfig::get('sf_lib_dir') . '/vendor/rackspace/lib');
      //$classLoader->register();
    }
  }

  /**
   * @return string filesystem path to base folder for all resized images
   */
  static public function getBaseDir()
  {
    return sfConfig::get('sf_web_dir') . DIRECTORY_SEPARATOR . self::$base_dir_name;
  }
  
  /**
   * Return purely the folder name
   * 
   * @return string
   */
  static public function getBaseDirName()
  {
  	return self::$base_dir_name;
  }

  /**
   * @return string url to base folder
   */
  static public function getBaseUrl()
  {
    return DIRECTORY_SEPARATOR . self::$base_dir_name;
  }
}
