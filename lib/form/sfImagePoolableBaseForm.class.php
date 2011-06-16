<?php
/**
 * Your app's BaseFormDoctrine class should extend this class so that all
 * sfImagePoolable objects' forms will process any selected images.
 * 
 * @author John Grimsey
 */
abstract class sfImagePoolableBaseForm extends sfFormDoctrine
{
  /**
   * Perform sfImagePool related logic to modify relations with images. For use
   * when the image pool image chooser widget is part of your form.
   *
   * @param array $values
   */
  public function doUpdateObject($values)
  {
    // if we have some image ids selected by the sfImagePoolChooser widget
    // then we need to assign them to the form's object.
    if(isset($values['sf_image_pool_ids']))
    {
      // Fix for empty image id 
      foreach ($values['sf_image_pool_ids'] as $idx => $image_id) { 
        if (empty($image_id)) unset($values['sf_image_pool_ids'][$idx]); 
      }
      
      if (!empty($values['sf_image_pool_ids'])) {
        $image_ids = $values['sf_image_pool_ids'];
        
        // if there is a featured image specified
        if(isset($image_ids['featured']))
        {
          $featured_id = $image_ids['featured'];
          $this->getObject()->setFeaturedImage($featured_id);
          unset($image_ids['featured']);
        }
  
        $this->getObject()->setImageIds($image_ids);
      }
    }
    
    parent::doUpdateObject($values);
  }  
    
  /**
   * @todo Refactor this to use a single widget and validator?
   */
  public function addMultipleUploadFields($field_name = 'image', $nb_images = 5)
  {
    for($i = 1; $i < $nb_images + 1; $i++)
    {
      $embedded_form = new sfImagePoolImageForm(new sfImagePoolImage, array('embedded' => true));
      $embed_name    = sprintf('%s_%u', $field_name, $i);
      
      $this->embedForm($embed_name, $embedded_form);
    }
  }
}