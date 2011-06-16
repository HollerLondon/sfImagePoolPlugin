<?php
class sfImagePoolResizer
{
    /**
     * 
     */
    public function __construct(sfImagePoolImage $image, $method, $width, $height)
    {
        $this->checkMethod($method);
        
        // expected values for method are 'scale' and 'crop', see plugin routing.yml
        // for default values.
        $this->image   = $image;
        $this->method  = $method;
        $this->scale   = ($method == 'scale') ? true : false;
        $this->options = ($this->scale) ? array() : array('method' => 'shave_all');
        $this->width   = $width;
        $this->height  = $height;
        
        $this->thumb = new sfThumbnail(
            $this->width,
            $this->height,
            $this->scale,
            true,                                // Inflate
            90,                                  // JPEG Quality
            'mySfImageMagickAdapter',            // Adapter
            $this->options
        );
        
        $this->thumb->loadFile($this->image->getPathToOriginalFile());
    }
    
    /**
     * @return sf
     */
    
    /**
     * Return saved sfThumbnail object 
     *
     * @return sfThumbnail
     */
    public function save()
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
                throw new Exception(sprintf('Could not create "%s"', $folder_path));
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
            throw new Exception(sprintf('"%s" is not a valid method', $method));
        }
    }
}
?>