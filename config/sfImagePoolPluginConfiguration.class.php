<?php
class sfImagePoolPluginConfiguration extends sfPluginConfiguration
{
    static $base_dir_name;
    
    /**
     * 
     */
    public function initialize()
    {
        /*$this->dispatcher->connect('routing.load_configuration', array(
            'sfImagePoolRouting' , 'listenToRoutingLoadConfigurationEvent'
        ));*/
        
        self::$base_dir_name = sfConfig::get('app_sf_image_pool_folder');
        
        if(!file_exists(self::getBaseDir()))
        {
            mkdir(self::getBaseDir(), 0777);
        }
        
        $this->connectTests();
    }
    
    /**
     * @return string filesystem path to base folder for all resized images
     */
    static public function getBaseDir()
    {
        return sfConfig::get('sf_web_dir') . DIRECTORY_SEPARATOR . self::$base_dir_name;       
    }    
    
    /**
     * @return string url to base folder
     */
    static public function getBaseUrl()
    {
        return DIRECTORY_SEPARATOR . self::$base_dir_name;       
    }
}
