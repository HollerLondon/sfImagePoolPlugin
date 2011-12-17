<?php
require_once(sfConfig::get('sf_plugins_dir') . '/sfImagePoolPlugin/config/sfImagePoolPluginConfiguration.class.php');

/**
 * PluginsfImagePoolImage form.
 *
 * @package    sfImagePoolPlugin
 * @subpackage form
 * @author     Ben Lancaster
 * @author     Jo Carter
 * @version    SVN: $Id: PluginsfImagePoolImage.class.php 23810 2009-11-12 11:07:44Z Ben Lancaster $
 */
abstract class PluginsfImagePoolImageForm extends BasesfImagePoolImageForm
{
  private $tag_fields = false;
  
  /**
   * Set up image form
   * 
   * Form options:
   * - use_tags - include tag field (if tagging = true)
   * - embedded - form embedded in object form (to add new images)
   */
  public function setup()
  {
    parent::setup();
    
    $this->useFields(array(
        'filename',
        'credit',
        'caption',
    ));
    
    $this->widgetSchema['caption'] = new sfWidgetFormInputText();
    $this->widgetSchema->setLabel('caption', 'Caption (Alt text)');
    
    // Override the default options
    $options = array(
      'required'   => $this->isNew(),
      'path'       => $this->getUploadDir()
    );
    
    $this->setWidget('filename', new sfWidgetFormInputFile());
    $this->setValidator('filename', new sfValidatorImageFile(sfImagePoolUtil::getValidatorOptions($options)), sfImagePoolUtil::getValidatorMessages());
    
    if ($this->getObject()->option('tagging') && $this->getOption('use_tags', true))
    {
      // namespace, key, widget-label
      $this->setTagWidget('Tags');
    }
    
    // If we're embedding a form then let's reduce it down just to filename
    // for now, and make it non-mandatory
    if ($this->getOption('embedded', false))
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
    if ($file = $this->getValue('filename'))
    {
      $previous_filename = $this->getObject()->getFilename();
      
      $this->deleteCached($previous_filename, $this->getObject());
      
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
   * 
   * Also need to delete sfImagePoolCrops - as the image itself has changed
   * this will handle deleting off the cloud
   * 
   * @param string $filename Old file to delete
   */
  public function deleteCached($filename, $image = null)
  {
    $count = sfImagePoolUtil::deleteImageFile($filename);
    
    if ($image)
    {
      $crops = $image->getCrops();
      
      if (0 < $crops->count())
      {
        foreach ($crops as $crop)
        {
          // Delete crop - interfaces with Cache
          $crop->delete();
          $crop->free();
        }
      }
    }
  }
  
  /**
   * Add a tags widget to the form, configured to use the given
   * namespace and key, and rendered with the given label.
   */
  public function setTagWidget($label, $namespace = null, $key = null)
  {
    if (!$label)
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
   * Get the field name for the tag field (different for each namespace)
   * 
   * @param mixed $namespace Optional namespace for tags
   * @param mixed $key       Optional key for tags
   * @return string
   */
  public function getTagInputName($namespace, $key)
  {
    if (!is_null($namespace))
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
   * @TODO: Might be a bit slow as it traverses all tags within all tag
   * fields, but might be fine.
   */
  public function applyTags()
  {
    if (!is_array($this->getTagFields()))
    {
      return;
    }
    
    // clear all tags first
    $this->getObject()->removeAllTags();
    
    // each tag field (each namespace/key combination)
    foreach ($this->getTagFields() as $name => $params)
    {
      foreach ($this->getTags($name) as $tag_value)
      {
        if (!is_null($params['namespace']) && !is_null($params['key']))
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
    
    if ($this->getObject()->option('tagging') && is_array($this->tag_fields) && !empty($this->tag_fields))
    {
      $this->applyTags();
    }
  }
}
