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
        ($this->resizer_options[2] ? 'scale' : 'crop'),
        $this->resizer_options[0],
        $this->resizer_options[1],
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
  
  public function commit()
  {
    
  }
} // END class 