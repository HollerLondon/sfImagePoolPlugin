<?php
class sfImagePoolResizer
{
    /**
     * Construct a new Resizer instance
     *
     * @param sfImagePoolImage $image 
     * @param string $method "crop" or "scale" 
     * @param integer $width 
     * @param integer $height 
     * @return array Parameters pass to sfThumbnail. 0 => width, 1 => height, 2 => method, 3 => allow scale up, 4=> jpg quality, 5 => Thumbnail adapter, 6 => adapter options
     */
    public function __construct(sfImagePoolImage $image, $method, $width, $height)
    {
        $this->checkMethod($method);
        
        
        // expected values for method are 'scale' and 'crop', see plugin routing.yml
        // for default values.
        $this->image      = $image;
        $this->method     = $method;
        $this->scale      = ($method == 'scale') ? true : false;
        $default_options  = sfConfig::get('app_sf_image_pool_adapter_options',array());
        $options          = ($this->scale) ? array() : array('method' => 'shave_all');
        $this->options    = array_merge($default_options, $options);
        $this->width      = $width;
        $this->height     = $height;
        
        // This is a tad clunky - we just want to return the params passed to 
        // sfThumbnail, but we can't use call_user_func_array to call a 
        // constructor. Reflection to the rescue!
        // http://www.php.net/manual/en/function.call-user-func-array.php#74427
        $params = array(
            $this->width,
            $this->height,
            $this->scale,
            true,                                // Inflate
            90,                                  // JPEG Quality
            sfConfig::get('app_image_pool_adapter','ImagePoolImageMagickAdapter'), // Adapter
            $this->options
        );

        $reflectionObj  = new ReflectionClass('sfThumbnail');
        $this->thumb    = $reflectionObj->newInstanceArgs($params);
        
        $this->thumb->loadFile($this->image->getPathToOriginalFile());
        return $params;
    }
    
    /**
     * Return saved sfThumbnail object 
     *
     * @return sfThumbnail
     */
    public function save($save_path)
    {
        $this->thumb->save($this->getNewImagePath());
        return $this->thumb;
    }
    
    /**
     * Create path to new image on filesystem. Creates folders
     * if they don't exist.
     *
     * @return string
     */
    protected function getNewImagePath()
    {
        $folder_path = implode(DIRECTORY_SEPARATOR, array(
            sfImagePoolPluginConfiguration::getBaseDir(),
            $this->method,
            $this->width,
            $this->height,
        ));
        
        // if folder not found for this resize, then attempt to create it.
        if(!file_exists($folder_path))
        {
            if(!mkdir($folder_path, 0777, true))
            {
                throw new sfImagePoolException(sprintf('Could not create "%s"', $folder_path));
            }
        }
        
        return $folder_path . DIRECTORY_SEPARATOR . $this->image['filename'];
    }
    
    /**
     * Check the given method is valid
     */
    protected function checkMethod($method)
    {
        if(!in_array($method, array('scale', 'crop')))
        {
            throw new sfImagePoolException(sprintf('"%s" is not a valid method', $method));
        }
    }
}
?>