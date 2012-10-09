<?php
/**
 * Template for object that has associated sfImagePoolImages
 * 
 * @package sfImagePoolPlugin
 * @subpackage template
 */
class sfImagePoolable extends Doctrine_Template
{
  private static $instance;
  
  /**
   * Get current instance
   * 
   * @return sfImagePoolable
   */
  public static function getInstance()
  {
    if (!self::$instance)
    {
        self::$instance = new self();
    }
    
    return self::$instance;
  }
  
  /**
   * Set the image pool listener to the table
   */
  public function setTableDefinition()
  {
    $this->addListener(new sfImagePoolableListener());
  }

  /**
   * Get the featured image for the object
   * 
   * @param Doctrine_Record $object
   */
  public function getFeaturedImage(Doctrine_Record $object = null)
  {
    $object = is_null($object) ? $this->getInvoker() : $object;
    return $this->getPoolImage($object);
  }
  
  /**
   * Set the featured images for the object
   * 
   * @param mixed $object_or_id
   */
  public function setFeaturedImage($object_or_id)
  {
    $image = ($object_or_id instanceof sfImagePoolImage)
      ? $object_or_id
      : sfImagePoolImageTable::getInstance()->findOneById($object_or_id);
      
    $this->getPoolImages()->setFeaturedImage($image);
  }
  
  /**
   * Get tag restrictions on the images to be displayed for association
   */
  public function getTagRestriction()
  {
    if ($restricted = $this->getOption('tag'))
    {
      return is_array($restricted) ? $restricted : array($restricted);
    }
    
    return false;
  }
  
  /**
   * Should this model allow multiple images to be associated with it?
   * Affects the rendering of the sfWidgetFormImagePoolChooser widget. 
   * 
   * @return boolean
   */
  public function allowSelectMultiple()
  {
    return $this->getOption('multiple', false);
  }
  
  /**
   * Return value of shared_images option, true by default
   * 
   * @return boolean
   */
  public function sharedImages()
  {
    return $this->getOption('shared_images', true);
  }
  
  /**
   * Make the query available so it can be customised 
   * 
   * @return Doctrine_Query
   */
  public function getImagePoolQuery()
  {
    return sfImagePoolImageTable::getInstance()->createQuery('i');      
  }

  /**
   * Get image pool images for an object
   * Fetch contents of the parameter holder from both namespaces (i.e. including featured image).
   * 
   * @param Doctrine_Record $object
   * @param Doctrine_Query $query
   * @return sfImagePoolImageCollection
   */
  public function getPoolImages(Doctrine_Record $object = null, Doctrine_Query $query = null)
  {
    $object = is_null($object) ? $this->getInvoker() : $object;
    $query  = is_null($query)  ? $this->getImagePoolQuery() : $query;
    
    if (!isset($object->_images) || !($object->_images instanceof sfImagePoolImageCollection))
    {
      $object->mapValue('_images', new sfImagePoolImageCollection('sfImagePoolImage'));
      
      $image_ids = sfImagePoolLookupTable::getInstance()->getImages($object, Doctrine_Core::HYDRATE_SINGLE_SCALAR);

      // If we don't have images - don't want to return all images
      if (!empty($image_ids)) 
      {
      	$query->andWhereIn('i.id',$image_ids);
      	
        if ($this->getOption('use_query_cache',false))
        {
          $query->useResultCache(true, 300, sprintf('images_for_%u_%s',$object->getPrimaryKey(),get_class($object)));
        }
        
        $images = $query->execute();
        $images = $this->matchOrder($images, $image_ids);

        $object->_images->merge($images);
        $object->_images->takeSnapshot();
      }
    }

    return $object->_images;
  }

  /**
   * When multiple images have been associated with an object via the image chooser widget,
   * the user may well have chosen a specific order. When pulling the associated images back
   * from the DB, Doctrine returns in primary key order, which is incorrect. This method
   * is a dirty way of matching the order of a collection to an order of image ids, which means
   * images are returned in the same order they were associated in.
   *
   * @param $images Doctrine_Collection of images
   * @param $image_ids Array of image ids in a specific order
   *
   * @return Doctrine_Collection
   */
  public function matchOrder(Doctrine_Collection $images, $image_ids)
  {
    if (!is_array($image_ids)) return $images;
    
    $ordered = new Doctrine_Collection('sfImagePoolImage', 'id');

    foreach($image_ids as $index => $id)
    {
      foreach($images as $i)
      {
        if($i['id'] == $id)
        {
          $ordered->set($i['id'], $i);
        }
      }
    }

    return $ordered;
  }
  
  /**
   * Get the number of pool images on this object
   *
   * @param Doctrine_Record $object
   * @param Doctrine_Query $query
   * @return int
   **/
  public function getNbPoolImages(Doctrine_Record $object = null, Doctrine_Query $query = null)
  {
    $object = is_null($object) ? $this->getInvoker() : $object;
    
    if (!$object->hasMappedValue('_nb_pool_images'))
    {
      // If already have getPoolImages _images mapped then get count
      if ($object->hasMappedValue('_images'))
      {
        $object->mapValue('_nb_pool_images', count($object->_images->count()));
      }
      else 
      {
        $image_ids = sfImagePoolLookupTable::getInstance()->getImages($object, Doctrine_Core::HYDRATE_SINGLE_SCALAR);
    
        $object->mapValue('_nb_pool_images', count($image_ids));
      }
    }
    
    return $object->_nb_pool_images;
  }
  
  /**
   * Get the featured image for an object
   * 
   * @param Doctrine_Record $object
   * @return sfImagePoolImage
   */
  public function getPoolImage(Doctrine_Record $object = null)
  {
    $object = is_null($object) ? $this->getInvoker() : $object;
    return $this->getPoolImages($object)->getFeatured();
  }

  
  /**
   * Get image tagged with a certain tag
   * 
   * @param string $tag
   * @param Doctrine_Record $object
   * @return sfImagePoolImage
   */
  public function getImageTaggedWith($tag, Doctrine_Record $object = null)
  {
  	$object = is_null($object) ? $this->getInvoker() : $object;
    return $this->getPoolImages($object)->getTaggedWith($tag);
  }
  
  
  /**
   * Fetch URL to the default pool image.
   * 
   * @todo Move the logic from the pool_image_uri() into this method and then
   * call this method from the helper for backwards compatibility.
   * 
   * @return string
   */    
  public function getDefaultPoolImageUrl($dimensions = '100x100', $method = 'crop', $absolute = false, Doctrine_Record $object = null)
  {
    $object = is_null($object) ? $this->getInvoker() : $object;
    
    // get main image
    $image = $this->getPoolImages($object)->getFeatured();

    // return url for the image
    return pool_image_uri($image, $dimensions, $method, $absolute);
  }
  
  /**
   * Set images to an object
   * 
   * @param array $images
   * @param Doctrine_Record $object
   */
  public function setImages($images = array(), Doctrine_Record $object = null)
  {
    $object = is_null($object) ? $this->getInvoker() : $object;

    $object->getPoolImages()->clear();
    
    foreach ($images as $image)
    {
      $object->getPoolImages()->add($image);
      
      if (!$object->allowSelectMultiple()) 
      {
        // Stop after adding one if object isn't allowed multiple images
        break;
      }
    }
  }
  
  /**
   * Will either add a new image to the object's collection
   * or if the shared_images = false and multiple = false
   * then it will replace the current.
   */
  public function addImage(sfImagePoolImage $image, Doctrine_Record $object = null)
  {
    $object = is_null($object) ? $this->getInvoker() : $object;
    
    if ($this->allowSelectMultiple())
    {
      $object->getPoolImages()->add($image);
    }
    else
    {
      // not a shared image so delete the current image completely
      if (!$this->sharedImages())
      {
        $object->getPoolImages()->delete();
      }
      // just clear the current image, don't delete it
      // as the images are shared (in the pool)
      else
      {
        $object->getPoolImages()->clear();
      }
      
      $object->getPoolImages()->add($image);
    }
  }
  
  /**
   * Perform sfImagePool related logic to modify relations with images. For use
   * when the image pool image chooser widget is part of your form.  Assign selected
   * images in widget to the object
   * 
   * Moved from sfImagePoolableBaseForm::doUpdateObject as shouldn't need to override
   * BaseForm to get this functionality to work.
   *
   * @param array $values
   * @author Jo Carter
   */
  public function setSfImagePoolIds($values)
  {
    if(!is_array($values))
    {
      $values = array();
    }

    // Fix for empty image ids
    //  - BL: May not be necessary since 9df5ac46b6d811c544d19d9760a071feea8c9dfa
    foreach ($values as $idx => $image_id) 
    { 
      if (empty($image_id)) unset($values[$idx]); 
    }
    
    if (!empty($values)) 
    {
      // if there is a featured image specified
      if (isset($values['featured']))
      {
        $featured_id = $values['featured'];
        $this->setFeaturedImage($featured_id);
        unset($values['featured']);
      }
    }
    
    $this->setImageIds($values);
  }
  
  /**
   * Method for setting images but from ids only.
   * 
   * @param $object
   * @param array $image_ids
   */
  public function setImageIds($image_ids = array(), Doctrine_Record $object = null)
  {
    $object = is_null($object) ? $this->getInvoker() : $object;
    
    if (empty($image_ids))
    {
    	return $object->getPoolImages()->clear();
    }
    
    $images = sfImagePoolImageTable::getInstance()->getByIds($image_ids);
    $images = $this->matchOrder($images, $image_ids);
    
    $this->setImages($images, $object);
  }
  
  /**
   * Does the invoker have the given sfImagePoolImage object associated?
   * 
   * @param sfImagePoolImage $image
   * @return boolean
   */
  public function hasImage($image = null)
  {
    // No image supplied - so of course not!
    if (is_null($image)) return false;
    
    if ($image instanceof sfOutputEscaper)
    {
    	$image = $image->getRawValue();
    }
  	
    foreach ($this->getInvoker()->getPoolImages() as $i)
    {
    	if ($i['id'] == $image['id'])
    	{
    	  return true;
    	}
    }
    
    return false;
  }
  
  /**
   * Does the object have images associated?
   * 
   * @return int
   */
  public function hasImages()
  {
    // we're not checking for a specific image, but simply for the presence of images (so count will do)
    return $this->getNbPoolImages();
  }
}