<?php
/**
 * undocumented class
 *
 * @package default
 * @author Ben Lancaster
 **/
abstract class sfImagePoolCache
{
  const CROP_IDENTIFER = 'local';
  const IS_REMOTE      = false;

  /**
   * Image pool object to work on
   *
   * @var object sfImagePoolImage
   **/
  protected $image;
  
  /**
   * Caching options
   * 
   * @var string[]
   */
  protected $options = array(
    'lifetime'    => 7776000,
  );
  
  /**
   * Options returned from sfImagePoolResizer - e.g width, height, etc
   *
   * @see sfImagePoolResizer::getParams()
   * @var array
   */
  protected $resizer_options = array();
  
  /**
   * Create an instance of the current caching interface using the appropriate class
   * as set in the configuration
   * 
   * @param sfImagePoolImage $image The image you are creating the cache adapter for
   * @param array $options          The caching options, these are set by default to the ones in the config if not defined/ overwritten
   * @param array $resizer_options  Parameters received from sfImagePoolResizer 
   *                                0 => width, 1 => height, 2 => method, 3 => allow scale up, 4 => jpg quality, 5 => Thumbnail adapter, 6 => adapter options
   * 
   * @return sfImagePoolCache implementation
   * 
   * @author Jo Carter
   */
  static public function getInstance(sfImagePoolImage $image, $options = array(), $resizer_options = array())
  {
    $configOptions        = sfConfig::get('app_sf_image_pool_cache',array());
    $options              = array_merge($configOptions, $options);
    
    $cache_class          = $options['class'];
    
    return new $cache_class($image, $options, $resizer_options);
  }
  
  public function __construct(sfImagePoolImage $image, $options = array(), $resizer_options = array())
  {
    $this->options          = array_merge($this->options, $options);
    $this->resizer_options  = array_merge($this->resizer_options, $resizer_options);
    $this->image            = $image;
  }
  
  /**
   * Set new / Change existing resizer options
   * 
   * @param array $resizer_options
   */
  public function setResizerOptions($resizer_options)
  {
    $this->resizer_options  = array_merge($this->resizer_options, $resizer_options);
  }
  
  public function sendCachingHttpHeaders()
  {
    return true;
  }
  
  public function getLifetime()
  {
    return $this->options['lifetime'];
  }
  
} // END class 