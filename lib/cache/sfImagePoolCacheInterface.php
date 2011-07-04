<?php
/**
 * Interface for all sfImagePool cache handlers
 *
 * @package default
 * @author Ben Lancaster
 **/
interface sfImagePoolCacheInterface
{
  public function __construct(sfImagePoolImage $image, $options = array());

  /**
   * Returns the destination for the given image
   *
   * @return string
   * @author Ben Lancaster
   **/
  public function getDestination();
  
  /**
   * Defines whether or not to send caching headers in the controller
   *
   * @return boolean
   * @author Ben Lancaster
   */
  public function sendCachingHttpHeaders();
  
  /**
   * Defines actions to be done after the thumbnail is created and prepared for sending to browser
   * e.g: sending to cloud
   */
  public function commit();
  
} // END interface sfImagePoolCache