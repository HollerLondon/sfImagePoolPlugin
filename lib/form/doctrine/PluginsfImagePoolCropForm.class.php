<?php

/**
 * PluginsfImagePoolCrop form.
 *
 * @package    sfImagePoolPlugin
 * @subpackage form
 * @author     Ben Lancaster
 * @version    SVN: $Id: sfImagePoolPlugin.php 23810 2009-11-12 11:07:44Z Ben Lancaster $
 */
abstract class PluginsfImagePoolCropForm extends BasesfImagePoolCropForm
{
  /**
   * New file location
   * @var string
   */
  protected $new_file;
  
  /**
   * Set up form
   */
  public function setup()
  {
    parent::setup();
    
    $this->setWidget('sf_image_id', new sfWidgetFormInputHidden());
    $this->setWidget('cropped_image', new sfWidgetFormInputFile());
    
    // Override from the default options
    $options = array(
        'required'   => true,
        'path'       => null  // Not saving these crops
    );
    
    $this->setValidator('cropped_image', new sfValidatorImageFile(sfImagePoolUtil::getValidatorOptions($options), sfImagePoolUtil::getValidatorMessages()));
    
    $this->useFields(array('sf_image_id', 'cropped_image'));
  }
  
  /**
   * Deal with returning the 'image' field to contain a string, and not
   * an sfValidatedFile object, which Doctrine turns its nose up at.
   */
  public function updateObject($values = null)
  {
    // if a new file is uploaded
    if ($file = $this->getValue('cropped_image'))
    {
      $object = parent::updateObject();   
      
      list($width, $height, $type, $attr) = getimagesize($file->getTempName());
      
      $cache  = sfImagePoolCache::getInstance($object->getImage(), array(), array('width'=>$width, 'height'=>$height, 'scale'=>false));
      
      $file->save($cache->getDestination());
      
      // now set the object's other columns, which weren't part of the upload form
      $object['width']      = $width;
      $object['height']     = $height;
      $object['is_crop']    = true;
      $object['location']   = $cache::CROP_IDENTIFIER;
      
      $this->new_file = $cache->commit(false);
    }
    else
    {
      $object = parent::updateObject();   
    }
    
    return $object;
  }     
  
  /**
   * @return string Path to the newly uploaded file
   */
  public function getPathToUpload()
  {
    return $this->new_file;
  }    
}
