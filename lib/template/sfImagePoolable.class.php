<?php
/**
 * Enter description here...
 */
class sfImagePoolable extends Doctrine_Template
{
    private static $instance;
    
    /**
     * @return sfImagePoolable
     */
    public static function getInstance()
    {
      if(!self::$instance)
      {
          self::$instance = new self();
      }
      
      return self::$instance;
    }
    
    /**
     * 
     */
    public function setTableDefinition()
    {
        $this->addListener(new sfImagePoolableListener());
    }

    public function getFeaturedImage(Doctrine_Record $object = null)
    {
      $object = is_null($object) ? $this->getInvoker() : $object;
      return $this->getPoolImage($object);
    }
    
    public function setFeaturedImage($object_or_id)
    {
      $image = ($object_or_id instanceof sfImagePoolImage)
        ? $object_or_id
        : Doctrine_Core::getTable('sfImagePoolImage')->findOneById($object_or_id);
        
      $this->getPoolImages()->setFeaturedImage($image);
    }
    
    public function getTagRestriction()
    {
      if($restricted = $this->getOption('tag'))
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
      return Doctrine_Core::getTable('sfImagePoolImage')->createQuery('i');      
    }

    /**
     * Fetch contents of the paramter holder from both namespaces (i.e. including featured image).
     * 
     * @return array
     */
    public function getPoolImages(Doctrine_Record $object = null, Doctrine_Query $query = null)
    {
      $object = is_null($object) ? $this->getInvoker() : $object;
      $query  = is_null($query)  ? $this->getImagePoolQuery() : $query;
      
      if(!isset($object->_images) || !($object->_images instanceof sfImagePoolImageCollection))
      {
        $object->mapValue('_images', new sfImagePoolImageCollection('sfImagePoolImage'));
        
        $image_ids = Doctrine_Core::getTable('sfImagePoolLookup')
          ->createQuery('l')
          ->select('l.sf_image_id')
          ->where('l.imaged_model_id = ?',$object['id'])
          ->andWhere('l.imaged_model = ?',get_class($object))
          ->execute(null,Doctrine_Core::HYDRATE_SINGLE_SCALAR);

        // If we don't have images - don't want to return all images
        if (!empty($image_ids)) 
        {
        	$query->andWhereIn('i.id',$image_ids);
        	
	        if($this->getOption('use_query_cache',false))
	        {
	          $query->useResultCache(true, 300, sprintf('images_for_%u_%s',$object->getPrimaryKey(),get_class($object)));
	        }
	        
	        $images = $query->execute();
	
	        $object->_images->merge($images);
	        $object->_images->takeSnapshot();
        }
      }

      return $object->_images;
    }
    
    /**
     * Get the number of pool images on this object
     *
     * @todo Refactor this and self::getPoolImages() properly as there's a lot of duplication
     **/
    public function getNbPoolImages(Doctrine_Record $object = null, Doctrine_Query $query = null)
    {
      $object = is_null($object) ? $this->getInvoker() : $object;
      $query  = is_null($query)  ? $this->getImagePoolQuery() : $query;
      
      if(!$object->hasMappedValue('_nb_pool_images'))
      {
        $image_ids = Doctrine_Core::getTable('sfImagePoolLookup')
          ->createQuery('l')
          ->select('l.sf_image_id')
          ->where('l.imaged_model_id = ?',$object['id'])
          ->andWhere('l.imaged_model = ?',get_class($object))
          ->execute(null,Doctrine_Core::HYDRATE_SINGLE_SCALAR);
      
        // If we don't have images - don't want to return all images
        if (!empty($image_ids)) 
        {
	        $query->andWhereIn('i.id',$image_ids);
	
	        $query->select('count(*)');
	        $nb_images = $query->execute(null,Doctrine_Core::HYDRATE_SINGLE_SCALAR);
        }
        else $nb_images = 0;
        
        $object->mapValue('_nb_pool_images',$nb_images);
      }
      
      return $object->_nb_pool_images;
    }
    
    public function getPoolImage(Doctrine_Record $object = null)
    {
      $object = is_null($object) ? $this->getInvoker() : $object;
      return $this->getPoolImages($object)->getFeatured();
    }

    /**
     * Just fetch URL to the default pool image.
     * 
     * @todo Move the logic from the sf_image_pool_image_url() into this method and then
     * call this method from the helper() for backwards compatibility.
     */    
    public function getDefaultPoolImageUrl($dimensions = '100x100', $method = 'crop', $absolute = false, Doctrine_Record $object = null)
    {
      $object = is_null($object) ? $this->getInvoker() : $object;
      
      // get main image
      $image = $this->getPoolImages($object)->getFeatured();

      // return url for the image
      return pool_image_uri($image, $dimensions, $method, $absolute);
    }
    
    public function setImages($images = array(),Doctrine_Record $object = null)
    {
      $object = is_null($object) ? $this->getInvoker() : $object;

      $object->getPoolImages()->clear();
      foreach($images as $image)
      {
        $object->getPoolImages()->add($image);
        if(!$object->allowSelectMultiple()) break;
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
      
      if($this->allowSelectMultiple())
      {
        $object->getPoolImages()->add($image);
      }
      else
      {
        // not a shared image so delete the current
        if(!$this->sharedImages())
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
     * Method for setting images but from ids only.
     * 
     * @param $object
     * @param array $image_ids
     */
    public function setImageIds($image_ids = array(),Doctrine_Record $object = null)
    {
      $object = is_null($object) ? $this->getInvoker() : $object;
      if(empty($image_ids))
      {
      	return $object->getPoolImages()->clear();
      }
      $images = sfImagePoolImageTable::getByIds($image_ids);
      $this->setImages($images,$object);
    }
    
    /**
     * Does the invoker have the given sfImagePoolImage object associated?
     */
    public function hasImage($image = null)
    {
      if($image instanceof sfOutputEscaper)
      {
      	$image = $image->getRawValue();
      }
    	
      foreach($this->getInvoker()->getPoolImages() as $i)
      {
      	if($i['id'] == $image['id'])
      	{
      	  return true;
      	}
      }
      
      return false;
    }
    
    public function hasImages()
    {
      // we're not checking for a specific image, but simply for the presence of images
      return $this->getPoolImages()->count();
    }
}