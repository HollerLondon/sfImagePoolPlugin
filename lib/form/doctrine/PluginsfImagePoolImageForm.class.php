<?php
require_once(sfConfig::get('sf_plugins_dir') . '/sfImagePoolPlugin/config/sfImagePoolPluginConfiguration.class.php');

/**
 * PluginsfImagePoolImage form.
 *
 * @package    ##PROJECT_NAME##
 * @subpackage form
 * @author     ##AUTHOR_NAME##
 * @version    SVN: $Id: sfDoctrineFormPluginTemplate.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
abstract class PluginsfImagePoolImageForm extends BasesfImagePoolImageForm
{
    /**
     * Enter description here...
     */
    public function setup()
    {
        parent::setup();
        
        $this->useFields(array(
            'filename',
            'credit',
            'caption',
        ));
        
        $max_size_bytes = sfConfig::get('app_sf_image_pool_maxfilesize');
        
        $this->setWidget('filename', new sfWidgetFormInputFile());
        $this->setValidator('filename', new sfValidatorImageFile(array(
            'max_width'  => 5000,
            'max_height' => 5000,
            'resize'     => false,
            'required'   => $this->isNew(),
            'max_size'   => $max_size_bytes,
            'mime_types' => sfConfig::get('app_sf_image_pool_mimes'),
            'path'       => $this->getUploadDir()
        )), array('mime_type' => 'Please upload either a JPG or PNG file', 'max_size' => sprintf('Files cannot exceed %sMb in size', round($max_size_bytes / 1024 / 1024))));
        
        if($this->getOption('use_tags', true))
        {
          // namespace, key, widget-label
          $this->setTagWidget('Category');
        }
        
        // if we're embedding a form then let's reduce it down just to filename
        // for now, and make it non-mandatory
        if($this->getOption('embedded', false))
        {
          $this->useFields(array('filename'));
          $this->getValidator('filename')->setOption('required', false);
        }
    }
    
    /**
     * Deal with returning the 'image' field to contain a string, and not
     * an sfValidatedFile object, which Doctrine turns its nose up at.
     */
    public function updateObject($values = null)
    {
        // if a new file is uploaded
        if($file = $this->getValue('filename'))
        {
            $previous_filename = $this->getObject()->getFilename();
            $this->deleteCached($previous_filename);
            
            $object = parent::updateObject($values);              
            
            $object['original_filename'] = $file->getOriginalName();
            $object['filename']          = basename($file->getSavedName());
            $object['mime_type']         = $file->getType();
            $object['title']             = $this->getValue('title');
            $object['caption']           = $this->getValue('caption');
            $object['credit']            = $this->getValue('credit');
        }
        else
        {
            $object = parent::updateObject($values);   
        }
        
        return $object;
    }    
    
    /**
     * Return path to folder where originals are stored.
     * 
     * @return string
     */
    public function getUploadDir()
    {
        return sfImagePoolPluginConfiguration::getBaseDir();
    }

    /**
     * Find all files in the sfImagePool filesystem with the given
     * name (i.e. all the resized versions) and delete them.
     */
    public function deleteCached($filename)
    {
        $search_root    = sfImagePoolPluginConfiguration::getBaseDir();
        $files_iterator = sfFinder::type('file')->name($filename)->in($search_root);
        
        foreach($files_iterator as $file)
        {
            unlink($file);
        }
    }
    
    private $tag_fields = false;
    
    /**
     * Add a tags widget to the form, configured to use the given
     * namespace and key, and rendered with the given label.
     */
    public function setTagWidget($label, $namespace = null, $key = null)
    {
        if(!$label)
        {
            throw new InvalidArgumentException('$label required');
        }
        
        $name = $this->getTagInputName($namespace, $key);
        
        $this->setWidget($name, new sfDoctrineTaggableInput());
        $this->setValidator($name, new sfValidatorPass());
        $this->getWidgetSchema()->setLabel($name, $label);
        
        // populate the tag field
        $this->setDefault($name, $this->getObject()->getTags(array(
            'is_triple' => !is_null($namespace) && !is_null($key),
            'namespace' => $namespace,
            'key'       => $key,
            'return'    => !is_null($namespace) && !is_null($key) ? 'value' : null,
        )));
        
        // store reference to this tag widget
        $this->tag_fields[$name] = array(
            'namespace' => $namespace,
            'key'       => $key
        );
    }
    
    /**
     * 
     */
    public function getTagInputName($namespace, $key)
    {
      if(!is_null($namespace))
      {
        return sprintf('image_tags_%s_%s', $namespace, $key);
      }
      else
      {
        return sprintf("image_tags");
      }
    }
    
    /**
     * @return array Array of all the tag field names
     */
    public function getTagFields()
    {
        return $this->tag_fields;
    }
    
    /**
     * Return the tag values of a tag field, exploded into an array
     * 
     * @return array
     */
    protected function getTags($field_name)
    {
        $tags = explode(",", $this->values[$field_name]);
        return array_map('trim', $tags);
    }
    
    /**
     * Attach tags to the object (prior to it being saved).
     * 
     * @todo Might be a bit slow as it traverses all tags within all tag
     * fields, but might be fine.
     */
    public function applyTags()
    {
      if(!is_array($this->getTagFields()))
      {
          return;
      }
      
      // clear all tags first
      $this->getObject()->removeAllTags();
      
      // each tag field (each namespace/key combination)
      foreach($this->getTagFields() as $name => $params)
      {
          // each tag
          foreach($this->getTags($name) as $tag_value)
          {
              if(!is_null($params['namespace']) && !is_null($params['key']))
              {
                // either construct tag with or without namespace according to tag field params
                $full_tag_string = (isset($params['namespace']) && !empty($params['namespace']))
                    ? sprintf('%s:%s=%s', $params['namespace'], $params['key'], $tag_value)
                    : sprintf('%s=%s', $params['key'], $tag_value);
                $this->getObject()->addTag($full_tag_string);
              }
              else
              {
                $this->getObject()->addTag($tag_value);
              }
          
          }
      }
    }
    
    /**
     * Just before the object is to be saved, attach tags to the
     * object if this form has had tag fields added.
     */
    public function doUpdateObject($values)
    {
        parent::doUpdateObject($values);
        
        if(is_array($this->tag_fields) && count($this->tag_fields))
        {
            $this->applyTags();
        }
    }
}
