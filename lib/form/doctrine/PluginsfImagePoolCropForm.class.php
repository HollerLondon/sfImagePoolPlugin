<?php

/**
 * PluginsfImagePoolCrop form.
 *
 * @package    ##PROJECT_NAME##
 * @subpackage form
 * @author     ##AUTHOR_NAME##
 * @version    SVN: $Id: sfDoctrineFormPluginTemplate.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
abstract class PluginsfImagePoolCropForm extends BasesfImagePoolCropForm
{
    protected $new_file;
    
    /**
     * 
     */
    public function setup()
    {
        parent::setup();
        
        $this->setWidget('sf_image_id', new sfWidgetFormInputHidden());
        $this->setWidget('cropped_image', new sfWidgetFormInputFile());
        
        $options = array(
            'resize'     => false,
            'required'   => true,
            'mime_types' => sfConfig::get('app_sf_image_pool_mimes'),
        );
        
        $messages = array('mime_types' => 'Please upload either a JPG or PNG file');
        $this->setValidator('cropped_image', new sfValidatorImageFile($options, $messages));
        
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
            
            $cache  = sfImagePoolCache::getInstance($object->getImage(), array(), array($width, $height, false));
            
            $file->save($cache->getDestination());
            
            // now set the object's width and height columns, which weren't part of the upload form
            $object['width']      = $width;
            $object['height']     = $height;
            $object['is_crop']    = true;
            $object['location']   = $cache::CROP_IDENTIFER;
            
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
