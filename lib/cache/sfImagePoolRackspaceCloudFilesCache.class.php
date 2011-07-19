<?php
/**
 * Rackspace Cloud Files caching adapter
 * 
 * Used 
 * Enter description here ...
 * @author jocarter
 *
 */
class sfImagePoolRackspaceCloudFilesCache extends sfImagePoolCache implements sfImagePoolCacheInterface
{
  /**
   * Rackspace cloud files container
   * 
   * @var CF_Container
   */
  private $container;
  
  /**
   * Array of Rackspace Cloud Files options
   *
   * @var array
   **/
  protected static $adapter_options = array(
    'auth_host'         => 'UK'
  );
  
  /**
   * Identifier for use with sfImagePoolCrop table
   * 
   * @var string
   */
  const CROP_IDENTIFER = 'rackspace';
  const IS_REMOTE      = true;
  
  public function __construct(sfImagePoolImage $image, $options = array(), $resizer_options = array())
  {
    parent::__construct($image, $options, $resizer_options);
    
    // Set up rackspace container
    $this->container = self::setup($options);
  }
  
  /**
   * Set up Rackspace cloud files - used by rackspace:initialise task as well as constructor
   * 
   * @see imagepoolRackspaceTask::execute()
   * @var array $options
   * @return CF_Container
   */
  public static function setup($options)
  {
    $required_fields = array('container','api_key','username');
    $adapter_options = $options['adapter_options'];
    
    foreach($required_fields as $f)
    {
      if(!array_key_exists($f, $adapter_options))
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
    return $this->container;
  }

  public function getDestination()
  {
    // Store in temp directory
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 
                                  ($this->resizer_options[2] ? 'scale' : 'crop') . '_' . 
                                  $this->resizer_options[0]  . '_' . 
                                  $this->resizer_options[1] . '_' . 
                                  $this->image['filename'];
  }
  
  public function commit($redirect = true)
  {
    // save to cloud
    $object_name = implode(DIRECTORY_SEPARATOR, array(
        ($this->resizer_options[2] ? 'scale' : 'crop'),
        $this->resizer_options[0],
        $this->resizer_options[1],
        $this->image['filename']
    ));
    
    $this->object = $this->container->create_object($object_name);
    $this->object->load_from_filename($this->getDestination()); 
    
    // clean up temp file
    unlink($this->getDestination());
    
    // check if crop exists
    $imageCrop = sfImagePoolCropTable::getInstance()->findCrop($this->image, $this->resizer_options[0], $this->resizer_options[1], self::CROP_IDENTIFER, !($this->resizer_options[2]));
    
    if (!$imageCrop) 
    {
      // create image crop
      $imageCrop = new sfImagePoolCrop();
    }
    
    // add/ update details
    $imageCrop->Image = $this->image;
    $imageCrop->width = $this->resizer_options[0];
    $imageCrop->height = $this->resizer_options[1];
    $imageCrop->location = self::CROP_IDENTIFER;
    $imageCrop->is_crop = !($this->resizer_options[2]);
    $imageCrop->save();
    
    // controller redirect 301 to cdn
    $url = $this->options['off_site_uri'] . DIRECTORY_SEPARATOR . $object_name;
    
    if ($redirect)
    {
      sfContext::getInstance()->getController()->redirect($url, 0, 301);
    }
    else
    {
      return $url;
    }
  }
} // END class 