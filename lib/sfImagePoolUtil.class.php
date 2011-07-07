<?php
class sfImagePoolUtil
{
  /**
   * Deletes an image, and all cached crops and scales.
   * 
   * @return integer Number of files deleted
   */
  static public function deleteImageFile($filename)
  {
    $search_root    = sfImagePoolPluginConfiguration::getBaseDir();
    $files_iterator = sfFinder::type('file')->name($filename)->in($search_root);

    $count = 0;
    
    foreach($files_iterator as $f)
    {
      unlink($f);
      $count++;
    }
    
    return $count;
  }
  
  /**
   * Add an image pool chooser widget to the form
   * 
   * Requires MooTools.More:
   *  * More/Fx.Reveal
   * 
   * @param $form Form object which to add widget to
   * @param $useSsl backend app uses SSL so return the MooTools JS with ssl
   */
  static public function addImageChooser($form, $useSsl = false)
  {
    $form->setWidget('sf_image_pool_ids', new sfWidgetFormImagePoolChooser(array(
      'object' => $form->getObject(), 'ssl' => $useSsl
    )));

    $form->setValidator('sf_image_pool_ids', new sfValidatorPass());    
  }
  
  
  /**
   * Add a MooEditable textarea widget with images from Image Pool as the selection
   * 
   * e.g: sfImagePoolUtil::addImagePoolMooEditable($this, 'summary', 'editorial');
   * 
   * Requires Javascripts and Stylesheets to be included in form
   * 
   *   public function getJavaScripts()
   *   {
   *     $js = parent::getJavascripts();
   *     
   *     return array_merge($js, array('/sfImagePoolPlugin/js/MooEditable.ImagePool.js'));
   *   }
   * 
   *   public function getStylesheets()
   *   {
   *         $css = parent::getStylesheets();
   *                 
   *     return array_merge($css, array('/sfImagePoolPlugin/css/MooEditable.ImagePool.css'=>'all'));
   *   }
   * 
   * Use this as a basis for any textarea which requires MooEditable + ImagePool as config
   * options are REQUIRED
   * 
   * @uses sfMooToolsFormExtraPlugin (REQUIRED)
   * @author Jo Carter <jocarter@holler.co.uk>
   * 
   * @param sfForm $form Form object which to add widget to
   * @param string $fieldName the field to set the textarea to
   * @param string $tag (optional) tag to restrict images for insertion - comma separated
   */
  static public function addImagePoolMooEditable($form, $fieldName, $tag = '')
  {
    sfApplicationConfiguration::getActive()->loadHelpers(array('Url'));
    
    $form->setWidget($fieldName,
      new sfWidgetFormTextareaMooEditable(array(
        'extratoolbar'  =>'imagepool',
        'config'        => sprintf(
          "chooserUrl: '%s',".
          "imageFolder: '%s',".
          "imageClass: 'image-pool'",
          url_for('sf_image_pool_chooser', array('tag'=>$tag)), sfConfig::get('app_sf_image_pool_folder')
        )
      ))
    );
  }
  
  /**
   * Add a validated image upload field to a form.
   * 
   * @param sfForm $form Form to modify
   * @param string $field_name Name of the image upload field, default 'image'  
   */
  static public function addImageField($form, $field_name = 'image')
  {
    $form->setWidget($field_name, new sfWidgetFormInputFile());
    $form->setValidator($field_name, new sfValidatorImageFile(self::getValidatorOptions(), self::getValidatorMessages()));
  }

  /**
   * @todo add ability to merge options with incoming array to allow easy overriding of an option.
   * @return array
   */
  static public function getValidatorOptions()
  {
    $max_size_bytes = sfConfig::get('app_sf_image_pool_maxfilesize');
    
    return array(
      'max_width'  => 5000,
      'max_height' => 5000,
      'resize'     => false,
      'required'   => false,
      'max_size'   => $max_size_bytes,
      'mime_types' => sfConfig::get('app_sf_image_pool_mimes'),
      'path'       => sfImagePoolPluginConfiguration::getBaseDir()
    );
  }
  
  /**
   * @return array
   */
  static public function getValidatorMessages()
  {
    $max_size_bytes = sfConfig::get('app_sf_image_pool_maxfilesize');
    
    return array(
      'mime_types' => 'Please upload either a JPG or PNG file',
      'max_size'   => sprintf('Files cannot exceed %sMb in size', round($max_size_bytes / 1024 / 1024))
    );    
  }

  /**
   * Perform the creation of an sfImagePool image and associate it with the
   * object being created by the given form. 
   * 
   * This method should be called after the call to isValid() returns true.
   * 
   * @return Object Form's saved and updated object. 
   */
  static public function processImageField($form, $field_name = 'image')
  {
    $file = $form->getValue($field_name);
    
    // if image uploaded then create image pool entry and associate
    // with the hotspot we're creating.
    if($file = $form->getValue($field_name))
    {
      // if the field name on the form is an embedded sfImagePoolForm
      // we'll need to step into an array of values on the field
      // in order to grab the sfValidatedFile object.
      if(is_array($file) && !is_null($file['filename']))
      {
        $file = $file['filename'];
      }
      // or if we have an array but it's empty, then let's skip
      else
      {
        // this method REALLY needs refactoring but I just can't concentrate
        // enough to do it, it's a bit scary, so much stuff relies on this working
        unset($form[$field_name]);
        $object = $form->save();
        return $object;
      }
      
      if($file instanceof sfValidatedFile)
      {
        $file->save();
        unset($form[$field_name]);
      
        $info = getimagesize($file->getSavedName());
      
        $image                      = new sfImagePoolImage();
        $image['filename']          = basename($file->getSavedName());
        $image['original_filename'] = $file->getOriginalName();
        $image['mime_type']         = $info['mime'];
        $image['title']             = $form->getValue('title');
        $image['caption']           = $form->getValue('caption');
        
        // the form update has to come after we've unset the image field
        $form->updateObject();
        $object = $form->getObject();
        $object->addImage($image);
      }
      else
      {
        // if no sfValidatedFile instance then we must still do
        // the form object.
        $form->updateObject();
        $object = $form->getObject();
      }
      
      $object->save();
    }
    else
    {
      unset($form[$field_name]);
      $object = $form->save();
    }
    
    return $object;
  }   

  /**
   * Take array of data for a single upload and create an image, assigning
   * content of $tags as tag (CSV string or single tag or array).
   * 
   * This common logic is abstracted out so it can easily be used by other plugins
   * that require image upload but need to handle the actual upload logic themselves.
   * 
   * @param array $upload
   * @param mixed $tags
   * @return sfImagePoolImage
   */
  static public function createImageFromUpload($upload, $tags = null)
  {
    // upload was ok, mime type ok and file isn't too big so let's move it into the image pool
    // location and then create a new object for it.
    $file = new sfValidatedFile(
      $upload['name'],
      $upload['type'],
      $upload['tmp_name'],
      $upload['size'],
      sfImagePoolPluginConfiguration::getBaseDir()
    );
    
    // this will generate the unique filename
    $new_filename = $file->save();
    
    $image_data = array(
      'original_filename' => $file->getOriginalName(),
      'filename'          => $new_filename,
      'mime_type'         => $file->getType(),
    );
    
    $image = new sfImagePoolImage();
    $image->fromArray($image_data);
    
    // now set tags if they've been supplied
    if($tags)
    {
      $image->addTag($tags);
    }
    
    $image->save();
    
    return $image;
  }    
  
  /**
   * Helper method for checking for upload errors when doing a basic non-Symfony Forms upload
   * which can sometimes be needed by other plugins (Symfony and/or Javascript). 
   * 
   * @param array $uploaded_file
   * @return array
   */
  static public function getUploadErrors($uploaded_file)
  {
    $errors         = array();
    $accepted_types = sfConfig::get('app_sf_image_pool_mimes');
    $max_size       = sfConfig::get('app_sf_image_pool_maxfilesize');
    
    if($uploaded_file['error'] !== 0)
    {
      switch($uploaded_file['error']) 
      { 
        case UPLOAD_ERR_INI_SIZE: 
            $message = sprintf('Maximum file size is %s', ini_get('upload_max_filesize'));
            break;

        case UPLOAD_ERR_FORM_SIZE: 
            $message = sprintf("Sorry, the maximum file size is %sMb", floor($max_size / 1024 / 1024));
            break;

        case UPLOAD_ERR_PARTIAL: 
            $message = 'The uploaded file was only partially uploaded'; 
            break;

        case UPLOAD_ERR_NO_FILE: 
            $message = 'No file was uploaded'; 
            break;

        case UPLOAD_ERR_NO_TMP_DIR: 
            $message = 'Missing a temporary folder'; 
            break;

        case UPLOAD_ERR_CANT_WRITE: 
            $message = 'Failed to write file to disk'; 
            break;

        case UPLOAD_ERR_EXTENSION: 
            $message = 'File upload stopped by extension';
            break;
             
        default: 
            $message = 'Unknown upload error';
            break;
      }           
      
      $errors[] = $message;
    }

    // if there aren't any stabndard php upload errors then we can do some checks of our own
    // as the file will actually have been uploaded. 
    elseif(!in_array($uploaded_file['type'], $accepted_types))
    {
      $errors[] = sprintf('%s is not supported', $uploaded_file['type']);
    }    
    
    return $errors;
  }
}  