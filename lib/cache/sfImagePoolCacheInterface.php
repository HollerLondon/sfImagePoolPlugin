<?php
/**
 * Interface for all sfImagePool cache handlers
 *
 * @package default
 * @author Ben Lancaster
 **/
interface sfImagePoolCacheInterface
{
  /**
   * Create new cache handler object
   * 
   * @param sfImagePoolImage $image The image you are creating the cache adapter for
   * @param array $options          The caching options, these are set by default to the ones in the config if not defined/ overwritten
   * @var array $resizer_options    Parameters received from sfImagePoolResizer 
   *                                0 => width, 1 => height, 2 => method, 3 => allow scale up, 4 => jpg quality, 5 => Thumbnail adapter, 6 => adapter options
   * @author Jo Carter
   */
  public function __construct(sfImagePoolImage $image, $options = array(), $resizer_options = array());

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
   * 
   * @var boolean $redirect Whether to redirect or not after the file has been committed (if false then return URL instead)
   * @author Jo Carter
   */
  public function commit($redirect = true);
  
  /**
   * Defines actions to be done when handling deletion of an sfImagePoolImage
   * 
   * Called by both the sfImagePoolImage and sfImagePoolImageCrop postDelete
   * 
   * @param sfImagePoolCrop $crop   An image pool crop
   * @author Jo Carter
   */
  public function delete(sfImagePoolCrop $crop = null);
  
} // END interface sfImagePoolCache