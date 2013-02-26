<?php
class sfImagePoolUtil
{
  /**
   * Deletes an image, and all cached crops and scales within the image-pool folder.
   * 
   * @param string $filename Filename to delete
   * @return integer Number of files deleted
   */
  static public function deleteImageFile($filename)
  {
    $search_root    = sfImagePoolPluginConfiguration::getBaseDir();
    $files_iterator = sfFinder::type('file')->name($filename)->in($search_root);
    $count = 0;
    
    foreach ($files_iterator as $f)
    {
      unlink($f);
      $count++;
    }
    
    return $count;
  }
  
  /**
   * Add an image pool chooser widget to the form
   * 
   * @requires MooTols Core
   * @requires MooTools.More:
   *  * More/Fx.Reveal
   * 
   * @param $form   Form object which to add widget to
   * @param $useSsl (@deprecated as JS dependancies should be in main project)
   */
  static public function addImageChooser($form, $useSsl = false)
  {
    $form->setWidget('sf_image_pool_ids', new sfWidgetFormImagePoolChooser(array('object' => $form->getObject())));

    $form->setValidator('sf_image_pool_ids', new sfValidatorPass());    
  }
  
  /**
   * Add a MooEditable textarea widget with images from Image Pool as the selection
   * 
   * Use this to add ImagePool to MooEditable with extra config as required as the image pool is REQUIRED
   * 
   * Example extension:
   *  sfImagePoolUtil::addImagePoolMooEditable($this, 'body', array('tag'=>'ingredients', 'width'=>100, 'height'=>100), array('extratoolbar' => 'tableadd | imagepool', 'height' => 150));
   * 
   * @uses sfMooToolsFormExtraPlugin (REQUIRED)
   * @uses custom image pool version to include javascripts and stylesheets
   * @author Jo Carter <jocarter@holler.co.uk>
   * 
   * @param sfForm $form      Form object which to add widget to
   * @param string $fieldName the field to set the textarea to
   * @param string $config    (optional) config - including tag to restrict images for insertion - comma separated; default width and height of image
   * @param array $options    (optional) additional MooEditable options (can use to override the extra toolbar without recreating this widget)
   */
  static public function addImagePoolMooEditable($form, $fieldName, $config = array(), $options = array())
  {
    // for backwards compatibility
    if (!is_array($config))
    {
      $config = array('tag' => $config);
    }
    
    // change config to options
    foreach ($config as $idx => $value) // tag / width / height
    {
      if (false === strstr($idx, 'image_')) // in case using the option format
      {
        $idx = 'image_' . $idx;
      }
      
      $options[$idx] = $value;
    }
    
    $form->setWidget($fieldName, new sfWidgetFormTextareaMooEditableImagePool($options));
  }
  
  /**
   * Add a validated image upload field to a form.
   * 
   * @param sfForm $form        Form to modify
   * @param string $field_name  Name of the image upload field, default 'image'  
   */
  static public function addImageField($form, $field_name = 'image')
  {
    $form->setWidget($field_name, new sfWidgetFormInputFile());
    $form->setValidator($field_name, new sfValidatorImageFile(self::getValidatorOptions(), self::getValidatorMessages()));
  }
  
  /**
   * Add multiple image upload fields to form
   * @todo Refactor this to use a single widget and validator?
   * 
   * Moved from sfImagePoolableBaseForm as this is now defunct
   * 
   * @param $form   Form object which to add widget to
   * @author Jo Carter
   */
  static public function addMultipleUploadFields($form, $field_name = 'image', $nb_images = 5)
  {
    for ($i = 1; $i < $nb_images + 1; $i++)
    {
      $embedded_form = new sfImagePoolImageForm(new sfImagePoolImage, array('embedded' => true));
      $embed_name    = sprintf('%s_%u', $field_name, $i);
      
      $form->embedForm($embed_name, $embedded_form);
    }
  }

  /**
   * Get image validator options
   * 
   * @param array $options Array of options to override default options
   * @return array
   */
  static public function getValidatorOptions($options = array())
  {
    $max_size_bytes = sfConfig::get('app_sf_image_pool_maxfilesize');
    
    $defaultOptions = array(
        'max_width'  => 5000,
        'max_height' => 5000,
        'resize'     => false,
        'required'   => false,
        'max_size'   => $max_size_bytes,
        'mime_types' => sfConfig::get('app_sf_image_pool_mimes'),
        'path'       => sfImagePoolPluginConfiguration::getBaseDir()
      );
      
    $options = array_merge($defaultOptions, $options);
    
    return $options;
  }
  
  /**
   * Get image validator messages
   * 
   * @param array $messages Array of messages to override default messages
   * @return array
   */
  static public function getValidatorMessages($messages = array())
  {
    $max_size_bytes = sfConfig::get('app_sf_image_pool_maxfilesize');
    
    $defaultMessages = array(
        'mime_types' => 'Please upload either a JPG or PNG file',
        'max_size'   => sprintf('Files cannot exceed %sMb in size', round($max_size_bytes / 1024 / 1024))
      );   

    $messages = array_merge($defaultMessages, $messages);
    
    return $messages;
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
    // with the model we're creating.
    if ($file = $form->getValue($field_name))
    {
      // if the field name on the form is an embedded sfImagePoolForm
      // we'll need to step into an array of values on the field
      // in order to grab the sfValidatedFile object.
      if (is_array($file) && !is_null($file['filename']))
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
      
      if ($file instanceof sfValidatedFile)
      {
        // Process file
        $image    = new sfImagePoolImage();
        $cache    = sfImagePoolCache::getInstance($image, array(), array());
        $filename = $file->generateFilename();
        
        // Save and commit file
        $file->save($cache->getDestination($filename));
        $cache->commitOriginal($filename, false);
        
        unset($form[$field_name]);
      
        $info = getimagesize($file->getSavedName());
        
        $image['filename']          = basename($file->getSavedName());
        $image['original_filename'] = $file->getOriginalName();
        $image['mime_type']         = $info['mime'];
        $image['original_width']    = $info[0];
        $image['original_height']   = $info[1];
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
   * Calculate the width and height of a scaled image
   * 
   * @param sfImagePoolImage $image
   * @param int $w
   * @param int $h
   * @return array
   */
  static public function calculateWidthAndHeight(sfImagePoolImage $image, $w, $h)
  {
    $sfThumb  = new sfThumbnail($w, $h, true, sfConfig::get('app_sf_image_pool_inflate', true));
    $loadFile = true;
    
    // Because it's not ideal loading the file each time, especially if stored on the cloud
    // there's a upgrade with width/ height in the image pool table.
    // But code won't break if not migrated.
    if ($image->getTable()->hasColumn('original_width'))
    {
      if (!$image->original_width)
      {
        list($image->original_width, $image->original_height) = getimagesize($image->getPathToOriginalFile());
        $image->save();
      }
      
      $sfThumb->initThumb($image->original_width, $image->original_height, $w, $h, true, sfConfig::get('app_sf_image_pool_inflate', true));
      $loadFile = false;
    }
    
    if ($loadFile) $sfThumb->loadFile($image->getPathToOriginalFile());
    
    $response = array($sfThumb->getThumbWidth(), $sfThumb->getThumbHeight());
    
    unset($sfThumb);
    
    return $response;
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
    
    // Process file
    $image    = new sfImagePoolImage();
    $cache    = sfImagePoolCache::getInstance($image, array(), array());
    $filename = $file->generateFilename();
        
    // Save and commit file
    $file->save($cache->getDestination($filename));
    $info     = getimagesize($cache->getDestination($filename)); // Get image info before commited, in case uploading to external source
    $cache->commitOriginal($filename, false);
    
    $image_data = array(
      'original_filename' => $file->getOriginalName(),
      'filename'          => basename($file->getSavedName()),
      'mime_type'         => $file->getType(),
    	'original_width'    => $info[0],
      'original_height'   => $info[1]
    );
    
    $image->fromArray($image_data);
    
    // now set tags if they've been supplied (and enabled)
    if ($tags && true === $image->option('tagging'))
    {
      $image->addTag($tags);
    }
    
    $image->save();
    
    return $image;
  } 

  /**
   * Create an image pool object from a validated file
   * 
   * @param sfValidatedFile $file
   * @param array $tags
   * @return sfImagePoolImage
   */
  static public function createImageFromValidatedFile(sfValidatedFile $file, $tags = null)
  {
    // Process file
    $image    = new sfImagePoolImage();
    $cache    = sfImagePoolCache::getInstance($image, array(), array());
    $filename = $file->generateFilename();
    
    // Save and commit file
    $file->save($cache->getDestination($filename));
    $info     = getimagesize($cache->getDestination($filename)); // Get image info before commited, in case uploading to external source
    $cache->commitOriginal($filename, false);
    
    $image_data = array(
      'filename'          => basename($file->getSavedName()),
      'original_filename' => $file->getOriginalName(),
      'mime_type'         => $file->getType(),
      'original_width'    => $info[0],
      'original_height'   => $info[1]
    );
    
    $image->fromArray($image_data);
    
    // now set tags if they've been supplied (and enabled)
    if ($tags && true === $image->option('tagging'))
    {
      $image->addTag($tags);
    }
    
    $image->save();
    
    return $image;
  }
  
  /**
   * Create an image pool object from a url
   * 
   * @param string $url
   * @param array $tags
   * @return sfImagePoolImage
   */
  static public function createImageFromUrl($url, $tags = null)
  {
    // Process file
    $info             = pathinfo($url);
    $image            = new sfImagePoolImage();
    $cache            = sfImagePoolCache::getInstance($image, array(), array());
    $filename         = sha1($info['basename'].rand(11111, 99999)).'.'.$info['extension'];
    
    // Save and commit file
    file_put_contents($cache->getDestination($filename), file_get_contents($url));
    $imageInfo        = getimagesize($cache->getDestination($filename)); // Get image info before commited, in case uploading to external source
    $cache->commitOriginal($filename, false);
    
    // Create image
    $image_data = array(
      'original_filename' => $info['basename'],
      'filename'          => $filename,
      'mime_type'         => $imageInfo['mime'],
      'original_width'    => $imageInfo[0],
      'original_height'   => $imageInfo[1]
    );
    
    $image->fromArray($image_data);
    
    // now set tags if they've been supplied (and enabled)
    if ($tags && true === $image->option('tagging'))
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
    
    if ($uploaded_file['error'] !== 0)
    {
      switch ($uploaded_file['error']) 
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
    // if there aren't any standard php upload errors then we can do some checks of our own
    // as the file will actually have been uploaded. 
    else if (!in_array($uploaded_file['type'], $accepted_types))
    {
      $errors[] = sprintf('%s is not supported', $uploaded_file['type']);
    }    
    
    return $errors;
  }
}  