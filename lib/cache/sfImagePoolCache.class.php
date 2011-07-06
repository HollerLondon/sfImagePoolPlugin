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
  
  public function __construct(sfImagePoolImage $image, $options = array(), $resizer_options = array())
  {
    $this->options          = array_merge($this->options, $options);
    $this->resizer_options  = array_merge($this->resizer_options, $resizer_options);
    $this->image            = $image;
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