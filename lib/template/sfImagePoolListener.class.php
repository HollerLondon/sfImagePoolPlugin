<?php
class sfImagePoolableListener extends Doctrine_Record_Listener
{
    /**
     * Enter description here...
     *
     * @param Doctrine_Event $event
     */
    public function postSave(Doctrine_Event $event)
    {
      $object = $event->getInvoker();
      $images = $object->getPoolImages();
      /**
       * Delete the images that was in the Collection when it was hydrated in
       * but isn't any more (uses Doctrine_Collection::getDeleteDiff())
       **/
      $delete_these = array();
      foreach($images->getDeleteDiff() as $i)
      {
        $delete_these[] = $i->getPrimaryKey();
      }
      
      
      if(!empty($delete_these))
      {
        $q = Doctrine_Core::getTable('sfImagePoolLookup')->createQuery('l')
              ->delete()
              ->andWhereIn('l.sf_image_id',$delete_these)
              ->andWhere('l.imaged_model = ?',get_class($object))
              ->andWhere('l.imaged_model_id = ?',$object->getPrimaryKey());

        $q->execute();
      }
      
      /**
       * Loop through the images, checking if they're to be inserted (do
       * nothing if so) then create a new sfImagePoolLookup as required
       **/
      foreach($images->getInsertDiff() as $im)
      {
        $lookup                     = new sfImagePoolLookup;
        $lookup['Image']            = $im;

        try
        {
          $im->save();
        }
        catch(Exception $e)
        {
          $image = Doctrine_Core::getTable('sfImagePoolImage')->findOneByFilename($im['filename']);
          if($image)
          {
            $lookup['Image'] = $image;
          }
          else
          {
            throw $e;
          }
        }
        
        $lookup['imaged_model']     = get_class($object);
        $lookup['imaged_model_id']  = $object->getPrimaryKey();
        $lookup['is_featured']      = ($images->getFeaturedImage() === $im);
        $lookup->save();
      }
      $images->takeSnapshot();
      return $object;
    }
    
    /**
     * Delete related images when this object is deleted
     *
     * @param Doctrine_Event $event
    */
    public function preDelete(Doctrine_Event $event)
    {
        $object = $event->getInvoker();
        
        // Default in documentation is that images are shared
        $images_shared = $this->getOption('shared_images');
        if (null === $images_shared) $images_shared = true;

        // if images are shared - delete only the lookups, leave images alone for other objects to use
        if ($images_shared)
        {
          sfImagePoolLookupTable::removeImages($object);
        }
        // if not shared and exclusive to model - delete the lookups, images and files.
        else
        {
          $object->getPoolImages()->delete();
        }
    }    
}