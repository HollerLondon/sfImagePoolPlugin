<?php
/**
 * undocumented class
 *
 * @package default
 * @author Ben Lancaster
 **/
class sfImagePoolFilesystemCache extends sfImagePoolCache implements sfImagePoolCacheInterface
{
  const CROP_IDENTIFIER = 'filesystem';
  const IS_REMOTE       = false;
  
  /**
   * Return path to new image on filesystem. Creates folders if they don't exist.
   *
   * @return string
   */
  public function getDestination()
  {
    $folder_path = implode(DIRECTORY_SEPARATOR, array(
        sfImagePoolPluginConfiguration::getBaseDir(),
        ($this->resizer_options['scale'] ? 'scale' : 'crop'),
        $this->resizer_options['width'],
        $this->resizer_options['height'],
    ));
    
    // if folder not found for this resize, then attempt to create it.
    if (!file_exists($folder_path))
    {
      if (!mkdir($folder_path, 0777, true))
      {
        throw new sfImagePoolException(sprintf('Could not create "%s"', $folder_path));
      }
    }
    
    return $folder_path . DIRECTORY_SEPARATOR . $this->image['filename'];
  }
  
  public function commit($redirect = true)
  {
    if (!$redirect) return $this->getDestination(); 
  }
  
  /**
   * Crops are always stored on the filesystem - they are only in the image crop table
   * if they have been manually uploaded
   * 
   * @see cache/sfImagePoolCache::exists()
   */
  public function exists()
  {
    if (file_exists($this->getDestination()))
    {
      try
      {
        // @TODO: NOTE: There may be a better way to do this - but php should use memory mapping here
        return file_get_contents($this->getDestination());
      }
      catch (Exception $e)
      {
        return false;s
      }
    }
    else
    {
      return false;
    }
  }
} // END class 