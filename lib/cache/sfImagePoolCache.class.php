<?php
/**
 * undocumented class
 *
 * @package default
 * @author Ben Lancaster
 **/
abstract class sfImagePoolCache
{
  /**
   * Identifier for use with sfImagePoolCrop table
   * Overwrite in individual implementations
   * 
   * @var string
   */
  const CROP_IDENTIFIER = 'local';
  
  /**
   * Whether the file is stored remotely
   * Overwrite in individual implementations
   * 
   * @var boolean
   */
  const IS_REMOTE       = false;

  /**
   * Image pool object to work on
   *
   * @var object sfImagePoolImage
   **/
  protected $image;
  
  /**
   * Caching options
   * 
   * @var array
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
  
  /**
   * Delete files in image pool folder - including all thumbnails
   * This is used by all cache implementations as the original file is stored on the filesystem
   * The main delete is only used to remove files if no crop sent through
   * 
   * @author Jo Carter
   */
  public function delete(sfImagePoolCrop $crop = null)
  {
    if (!is_null($crop))
    {
      $count = sfImagePoolUtil::deleteImageFile($this->image['filename']);
    }
  }
   
  /** 
   * Checks if there's a processed version of the requested image already
   *
   * @return mixed false if not, or the URI to the resource if available
   * @author Ben Lancaster
   **/
  protected function alreadyExists()
  {
    return Doctrine_Core::getTable('sfImagePoolCrop')
      ->createQuery('c')
      ->select('id')
      ->innerJoin('c.Image')
      ->where('c.sf_image_id = ?',$this->image['id'])
      ->andWhere('c.width = ?', $this->resizer_options['width'])
      ->andWhere('c.height = ?', $this->resizer_options['height'])
      ->andWhere('c.is_crop = ?',!$this->resizer_options['scale'])
      ->andWhere('c.location != ?',sfImagePoolCache::CROP_IDENTIFIER)
      ->execute(null,Doctrine_Core::HYDRATE_SINGLE_SCALAR);
  }
  
} // END class 