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
   * @var CF_Container
   */
  private $container = null;
  
  /**
   * Array of Rackspace Cloud Files options
   *
   * @var array
   **/
  protected static $adapter_options = array(
    'auth_host'         => 'UK'
  );
  
  const CROP_IDENTIFIER = 'rackspace';
  const IS_REMOTE      = true;
  
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
    
    $auth = new CF_Authentication($adapter_options['username'], $adapter_options['api_key'], null, ('UK' == $adapter_options['auth_host'] ? UK_AUTHURL : US_AUTHURL));
    $auth->authenticate();
    $conn = new CF_Connection($auth);
    
    try 
    {
      $container = $conn->get_container($adapter_options['container']);
    }
    catch (NoSuchContainerException $e)
    {
      // Container doesn't already exist so create it
      $container = $conn->create_container($adapter_options['container']);
      $container->make_public();
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

  public function getDestination()
  {
    // Store in temp directory
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 
                                  ($this->resizer_options['scale'] ? 'scale' : 'crop') . '_' . 
                                  $this->resizer_options['width']  . '_' . 
                                  $this->resizer_options['height'] . '_' . 
                                  $this->image['filename'];
  }
  
  /**
   * Get the filename (to be) stored on the cloud
   * 
   * @author Jo Carter
   * @param array $resizer_options  Allows overriding of the width, height, scaling options
   */
  public function getCloudName($resizer_options = array())
  {
    $resizer_options = array_merge($this->resizer_options, $resizer_options);
    
    $object_name = implode('/', array(
        ($resizer_options['scale'] ? 'scale' : 'crop'),
        $resizer_options['width'],
        $resizer_options['height'],
        $this->image['filename']
    ));
    
    return $object_name;
  }
  
  public function commit($redirect = true)
  {
    // save to cloud
    $object_name = $this->getCloudName();
    $container   = $this->getContainer();
    
    $this->object = $container->create_object($object_name);
    $this->object->load_from_filename($this->getDestination()); 
    
    // clean up temp file
    unlink($this->getDestination());
    
    // check if crop exists
    $imageCrop = sfImagePoolCropTable::getInstance()->findCrop($this->image, $this->resizer_options['width'], $this->resizer_options['height'], !($this->resizer_options['scale']), self::CROP_IDENTIFIER);
    
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
    $ssl = sfContext::getInstance()->getRequest()->isSecure();
    $off_site_index = ($ssl ? 'off_site_ssl_uri' : 'off_site_uri');
  
    $url = $this->options[$off_site_index] . DIRECTORY_SEPARATOR . $object_name;

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
    
    if ($redirect)
    {
      sfContext::getInstance()->getController()->redirect($url, 0, 301);
    }
    else
    {
      return $url;
    }
  }
  
  public function delete(sfImagePoolCrop $crop = null)
  {
    parent::delete($crop);
    
    // Then deal with stuff on the edge - delete crop from edge
    if ($crop)
    {
      $resizer_options = array('width'=>$crop->width, 'height'=>$crop->height, 'scale'=>(!$crop->is_crop));
      $object_name = $this->getCloudName($resizer_options);
      
      try 
      {
        $this->container->delete_object($object_name);
      }
      catch (NoSuchObjectException $e)
      {
        // Image already deleted from cloud - that's ok
      }
    }
  }
  
  /**
   * Check crop exists
   * @see cache/sfImagePoolCacheInterface::exists()
   */
  public function exists()
  {
    $imageCropExists = sfImagePoolCropTable::getInstance()->getCropExistance($this->image, $this->resizer_options['width'], $this->resizer_options['height'], !($this->resizer_options['scale']), self::CROP_IDENTIFIER);
    
    if (false === $imageCropExists)
    {
      return false;
    }
    else 
    {
      $object_name = $this->getCloudName();
      
      $ssl = sfContext::getInstance()->getRequest()->isSecure();
      $off_site_index = ($ssl ? 'off_site_ssl_uri' : 'off_site_uri');
    
      $url = $this->options[$off_site_index] . DIRECTORY_SEPARATOR . $object_name;
      
      sfContext::getInstance()->getController()->redirect($url, 0, 301);
      
      return $url; // or true // or whatever ;)
    }
  }
} // END class 