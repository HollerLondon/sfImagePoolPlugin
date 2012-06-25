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
   * @param string $filename Filename to save to if original file
   * @return string
   */
  public function getDestination($filename = null)
  {
    $imagePath = array(sfImagePoolPluginConfiguration::getBaseDir());
    
    // If crop
    if (is_null($filename))
    {
      $imagePath = array_merge($imagePath, array(($this->resizer_options['scale'] ? 'scale' : 'crop'),
																			           $this->resizer_options['width'],
																			           $this->resizer_options['height']));
      
      $filename = $this->image['filename'];
    }
    
    $folder_path = implode(DIRECTORY_SEPARATOR, $imagePath);
    
    // if folder not found for this resize, then attempt to create it.
    if (!file_exists($folder_path))
    {
      if (!mkdir($folder_path, 0777, true))
      {
        throw new sfImagePoolException(sprintf('Could not create "%s"', $folder_path));
      }
    }
    
    return $folder_path . DIRECTORY_SEPARATOR . $filename;
  }
  
  public function getPathToOriginalFile()
  {
    return $this->getDestination($this->image['filename']);
  }
  
  public function commit($redirect = true)
  {
    if (!$redirect) return $this->getDestination(); 
  }
  
  public function commitOriginal($filename, $redirect = true)
  {
    if (!$redirect) return $this->getDestination($filename); 
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
        // Do a redirect to the file on filesystem - don't load contents in - that costs memory
        $url = str_replace(sfImagePoolPluginConfiguration::getBaseDir(), sfImagePoolPluginConfiguration::getBaseUrl(), $this->getDestination());
        
        sfContext::getInstance()->getController()->redirect($url, 0, 301);
        
        return $url;
      }
      catch (Exception $e)
      {
        return false;
      }
    }
    else
    {
      return false;
    }
  }
} // END class 