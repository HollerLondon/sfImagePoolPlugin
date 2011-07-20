<?php
/**
 * Interface for sfThumbnail
 *
 * @package symfony
 * @subpackage sfImagePoolPlugin
 * @author Ben Lancaster
 */
class sfImagePoolResizer
{
    /**
     * Array of parameters passed to sfThumbnail
     *
     * @var array
     **/
    private $params = array();
    
    /**
     * Image to resize
     *
     * @var sfImagePoolImage
     **/
    private $image;
    
    /**
     * crop or scale
     *
     * @var string
     **/
    private $method = 'crop';
    
    /**
     * options passed to the sfThumbnail adapter
     *
     * @var array
     **/
    private $options = array();
    
    /**
     * Width
     *
     * @var int
     **/
    private $width;
    
    /**
     * Height
     *
     * @var int
     **/
    private $height;
    
    /**
     * True if self::$method is 'scale'
     *
     * @var boolean
     * @see self::$method
     **/
    private $scale = false;
    
    /**
     * Instance of sfThumbnail from sfThumbnailPlugin
     *
     * @var sfThumbnail
     **/
    private $thumb;
    
    /**
     * Construct a new Resizer instance
     *
     * @param sfImagePoolImage $image 
     * @param string $method "crop" or "scale" 
     * @param integer $width 
     * @param integer $height 
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
        $this->params = array(
            $this->width,
            $this->height,
            $this->scale,
            true,                                // Inflate
            90,                                  // JPEG Quality
            sfConfig::get('app_image_pool_adapter','ImagePoolImageMagickAdapter'), // Adapter
            $this->options
        );
        
        $reflectionObj  = new ReflectionClass('sfThumbnail');
        $this->thumb    = $reflectionObj->newInstanceArgs($this->params);
        
        $this->thumb->loadFile($this->image->getPathToOriginalFile());
    }
    
    /**
     * Get the sfThumbnail parameters
     *
     * @return array Parameters passed to sfThumbnail. 0 => width, 1 => height, 2 => method, 3 => allow scale up, 4 => jpg quality, 5 => Thumbnail adapter, 6 => adapter options
     * @author Ben Lancaster
     **/
    public function getParams()
    {
      $keys = array('width','height','scale','inflate','quality','adapter','adapter_options');
      $with_keys = array_combine($keys,$this->params);
      return array_merge($with_keys,$this->params);
      return $this->params;
    }
    
    /**
     * Return saved sfThumbnail object 
     *
     * @param $save_path   Save path - sent through from sfImagePoolCache{type}
     * @see   sfImagePoolCacheInterface::getDestination()
     * @return sfThumbnail
     */
    public function save($save_path)
    {
        $this->thumb->save($save_path);
        return $this->thumb;
    }
    
    /**
     * Check the given method is valid
     */
    protected function checkMethod($method)
    {
        if (!in_array($method, array('scale', 'crop')))
        {
            throw new sfImagePoolException(sprintf('"%s" is not a valid method', $method));
        }
    }
}
?>