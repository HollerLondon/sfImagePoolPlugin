<?php
/**
 * Your app's BaseFormDoctrine class should extend this class so that all
 * sfImagePoolable objects' forms will process any selected images.
 * 
 * NOTE: This functionality has been moved to sfImagePoolable::setSfImagePoolIds, 
 * and sfImagePoolUtil::addMultipleUploadFields
 * 
 * @deprecated
 * @author John Grimsey
 */
abstract class sfImagePoolableBaseForm extends sfFormDoctrine
{
  public function addMultipleUploadFields($field_name = 'image', $nb_images = 5)
  {
    sfImagePoolUtil::addMultipleUploadFields($this, $field_name, $nb_images);
  }
}