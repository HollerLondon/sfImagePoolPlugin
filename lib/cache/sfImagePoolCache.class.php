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
  private $image;
  
  /**
   * Caching options
   *
   * @var string
   */
  private $options = array(
    'lifetime'    => 7776000,
  );
  
  public function __construct(sfImagePoolImage $image, $options = array())
  {
    $this->options = array_merge($this->options, $options);
    $this->image   = $image;
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