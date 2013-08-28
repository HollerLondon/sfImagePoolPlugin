<?php

/**
 * Rackspace Cloud Files caching adapter
 * 
 * @author jocarter
 */
class sfImagePoolRackspaceCloudFilesCache extends sfImagePoolCache implements sfImagePoolCacheInterface
{
  /**
   * Rackspace cloud files container
   * 
   * @var OpenCloud/Container
   */
  private $container = null;
  
  /**
   * Array of Rackspace Cloud Files options
   * 
   * Dallas/Fort Worth (identified by DFW)
   * Chicago (identified by ORD)
   * London (identified by LON).
   *
   * @var array
   **/
  protected static $adapter_options = array(
    'auth_host'         => 'LON'
  );
  
  const CROP_IDENTIFIER = 'rackspace';
  const IS_REMOTE        = true;
  
  public function __construct(sfImagePoolImage $image, $options = array(), $resizer_options = array())
  {
    parent::__construct($image, $options, $resizer_options);
  }
  
  /**
   * Set up Rackspace cloud files - used by rackspace:initialise task as well as constructor
   * 
   * @see rackspaceInitialiseTask::execute()
   * @var array $options    Passed through from __construct
   * @return CF_Container
   */
  public static function setup($options)
  {
    $required_fields = array('container','api_key','username');
    $adapter_options = $options['options'];
    
    foreach ($required_fields as $f)
    {
      if (!array_key_exists($f, $adapter_options))
      {
        throw new InvalidArgumentException(sprintf("Missing option '%s' is required",$f));
      }
    }
    
    $adapter_options = array_merge(self::$adapter_options, $adapter_options);
    
    // Backwards compatible
    if ('UK' == $adapter_options['auth_host'])      $adapter_options['auth_host'] = 'LON';
    else if ('US' == $adapter_options['auth_host']) $adapter_options['auth_host'] = 'DFW';
    
    // https://github.com/rackspace/php-opencloud/blob/master/docs/userguide/collections.md
    // https://github.com/rackspace/php-opencloud/blob/master/docs/quickref.md#ostore
    // http://php-opencloud.com
    // https://github.com/rackspace/php-opencloud/blob/master/samples/objectstore/container.php
    
    $conn = new \OpenCloud\Rackspace(
      ('LON' == $adapter_options['auth_host'] ? RACKSPACE_UK : RACKSPACE_US),
      array(
          'username' => $adapter_options['username'],
          'apiKey'   => $adapter_options['api_key']
      ));
      
    $conn->SetDefaults('ObjectStore', RAXSDK_OBJSTORE_NAME, $adapter_options['auth_host'], RAXSDK_OBJSTORE_URLTYPE);
    
    $ostore = $conn->ObjectStore(); // uses default values
    
    try 
    {
      $container = $ostore->Container($adapter_options['container']);
    }
    catch (\OpenCloud\Base\Exceptions\ContainerNotFoundError $e)
    {
      // Container doesn't already exist so create it
      try
      {
        $container = $ostore->Container();
        $container->Create(array('name'=>$adapter_options['container']));
        $container->EnableCDN();
      }
      catch (\OpenCloud\Base\Exceptions\CdnHttpError $e) 
      {
        throw new Exception'Container did not publish - please publish manually');
      }
    }
    
    return $container;
  }
  
  /**
   * Return the container for the Rackspace files
   * 
   * @return CF_Container
   */
  public function getContainer()
  {
    if (!$this->container)
    {
      // Set up rackspace container when needed only
      $this->container = self::setup($this->options);
    }
    
    return $this->container;
  }

  /**
   * Return the filesystem location for the thumbnail / original
   * file
   * 
   * @param string $filename Send through filename if getting original file
   * @return string Filesystem location
   */
  public function getDestination($filename = null)
  {
    if (is_null($filename))
    {
      $filename = ($this->resizer_options['scale'] ? 'scale' : 'crop') . '_' . 
                  $this->resizer_options['width']  . '_' . 
                  $this->resizer_options['height'] . '_' . 
                  $this->image['filename'];
    }
    
    // Store in temp directory
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
  }
  
  /**
   * Used to create thumbnails, this gets the original file from Rackspace
   * and stores it in the /tmp directory
   * 
   * @return string File location
   * @throws sfFileException if file can't be downloaded
   */
  public function getPathToOriginalFile()
  {
    $object_name = $this->getCloudName(array(), $this->image['filename']);
      
    $ssl             = sfContext::getInstance()->getRequest()->isSecure();
    $off_site_index = ($ssl ? 'off_site_ssl_uri' : 'off_site_uri');
    
    $url = $this->options[$off_site_index] . DIRECTORY_SEPARATOR . $object_name;
    
    // Save to tmp dir and return
    $tmpLocation = $this->getDestination($this->image['filename']);
    
    if (file_exists($tmpLocation)) return $tmpLocation;
    else
    {
      // Download file and save in tmp dir
      // @TODO: This may need refactoring
      $written = file_put_contents($tmpLocation, file_get_contents($url));
      
      if (false !== $written && 0 < $written) return $tmpLocation;
      else throw new sfFileException('Could not download cloud file');
    }
  }
  
  /**
   * Get the filename (to be) stored on the cloud
   * 
   * @author Jo Carter
   * @param array $resizer_options  Allows overriding of the width, height, scaling options
   */
  public function getCloudName($resizer_options = array(), $filename = null)
  {
    $resizer_options = array_merge($this->resizer_options, $resizer_options);
    
    if (is_null($filename))
    {
      $object_name = implode('/', array(
                                    ($resizer_options['scale'] ? 'scale' : 'crop'),
                                    $resizer_options['width'],
                                    $resizer_options['height'],
                                    $this->image['filename']
      ));
    }
    else $object_name = $filename;
    
    return $object_name;
  }
  
  /**
   * Commit thumbnail/ crop to cloud
   * 
   * @param boolean $redirect
   * @throws Doctrine_Connection_Exception
   * @return string url of cloud file (unless redirect is true)
   */
  public function commit($redirect = true)
  {
    // save to cloud
    $object_name = $this->getCloudName();
    $container   = $this->getContainer();
    
    $this->object = $container->DataObject();
    $this->object->Create(array('name'=>$object_name, 'content_type'=>$this->image->mime_type), $this->getDestination());
    
    // clean up temp file
    unlink($this->getDestination());
    
    // check if crop exists
    $imageCrop = sfImagePoolCropTable::getInstance()->findCrop($this->image, 
                                                               $this->resizer_options['width'], 
                                                               $this->resizer_options['height'], 
                                                               !($this->resizer_options['scale']), 
                                                               self::CROP_IDENTIFIER);
    
    if (!$imageCrop) 
    {
      // create image crop
      $imageCrop = new sfImagePoolCrop();
    }
    
    // add/ update details
    $imageCrop->Image     = $this->image;
    $imageCrop->width     = $this->resizer_options['width'];
    $imageCrop->height    = $this->resizer_options['height'];
    $imageCrop->location  = self::CROP_IDENTIFIER;
    $imageCrop->is_crop   = !($this->resizer_options['scale']);
    
    // controller redirect 301 to cdn
    // If we are on a secure page we want to use the ssl option to avoid security warnings
    $ssl            = sfContext::getInstance()->getRequest()->isSecure();
    $off_site_index = ($ssl ? 'off_site_ssl_uri' : 'off_site_uri');
    $url            = $this->options[$off_site_index] . DIRECTORY_SEPARATOR . $object_name;

    // There's a chance that save() will fail because the crop already exists
    // in the database (race condition). So, if it does fail, let's try and grab it. If it 
    // failed and it doesn't exist, then re-throw the exception
    try
    {
      $imageCrop->save();
    }
    catch (Doctrine_Connection_Exception $e)
    {
      if ($e->getPortableCode() != Doctrine_Core::ERR_ALREADY_EXISTS) throw $e;
    }
    
    if ($redirect) sfContext::getInstance()->getController()->redirect($url, 0, 301);
    else return $url;
  }
  
  /**
   * Commit original file to cloud
   * 
   * @param string $filename
   * @param boolean $redirect
   * @return string url of cloud file or redirect
   */
  public function commitOriginal($filename, $redirect = true)
  {
    // save to cloud
    $object_name = $this->getCloudName(array(), $filename);
    $container   = $this->getContainer();
    
    $this->object = $container->DataObject();
    $this->object->Create(array('name'=>$object_name, 'content_type'=>$this->image->mime_type), $this->getDestination($filename));
    
    // clean up temp file
    unlink($this->getDestination($filename));
    
    // controller redirect 301 to cdn
    // If we are on a secure page we want to use the ssl option to avoid security warnings
    $ssl = sfContext::getInstance()->getRequest()->isSecure();
    $off_site_index = ($ssl ? 'off_site_ssl_uri' : 'off_site_uri');
  
    $url = $this->options[$off_site_index] . DIRECTORY_SEPARATOR . $object_name;

    if ($redirect) sfContext::getInstance()->getController()->redirect($url, 0, 301);
    else return $url;
  }
  
  /**
   * Delete image pool from cloud
   * 
   * @param sfImagePoolCrop $crop
   */
  public function delete(sfImagePoolCrop $crop = null)
  {
    parent::delete($crop);
    
    $container   = $this->getContainer();
    
    // Then delete thumbnails from cloud
    if ($crop)
    {
      try 
      {
        $resizer_options = array('width'=>$crop->width, 'height'=>$crop->height, 'scale'=>(!$crop->is_crop));
        $object_name = $this->getCloudName($resizer_options);
        $object = $container->DataObject($object_name);
        $object->Delete();
      }
      catch (\OpenCloud\Base\Exceptions\DeleteError $e)
      {
        // Image already deleted from cloud - that's ok
      }
    }
    else
    {
      // Delete original from the cloud
      try 
      {
        $object_name = $this->getCloudName(array(), $this->image->filename);
        $object = $container->DataObject($object_name);
        $object->Delete();
      }
      catch (\OpenCloud\Base\Exceptions\DeleteError $e)
      {
        // Image already deleted from cloud - that's ok
      }
    }
  }
  
  /**
   * Check if crop exists
   * 
   * @see cache/sfImagePoolCacheInterface::exists()
   */
  public function exists()
  {
    $imageCropExists = sfImagePoolCropTable::getInstance()->getCropExistance($this->image, 
                                                                             $this->resizer_options['width'], 
                                                                             $this->resizer_options['height'], 
                                                                             !($this->resizer_options['scale']), 
                                                                             self::CROP_IDENTIFIER);
    
    if (false === $imageCropExists) return false;
    else 
    {
      $object_name = $this->getCloudName();
      
      $ssl            = sfContext::getInstance()->getRequest()->isSecure();
      $off_site_index = ($ssl ? 'off_site_ssl_uri' : 'off_site_uri');
      $url             = $this->options[$off_site_index] . DIRECTORY_SEPARATOR . $object_name;
      
      sfContext::getInstance()->getController()->redirect($url, 0, 301);
      
      return $url; // or true // or whatever ;)
    }
  }
} // END class 
