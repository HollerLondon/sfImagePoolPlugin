<?php
require_once dirname(__FILE__).'/../lib/sfImagePoolAdminGeneratorConfiguration.class.php';
require_once dirname(__FILE__).'/../lib/sfImagePoolAdminGeneratorHelper.class.php';

/**
 * sfImagePoolAdmin actions.
 *
 * @package    symfony
 * @subpackage sfImagePoolPlugin
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class sfImagePoolAdminActions extends autoSfImagePoolAdminActions
{
    /**
     * Override this action so we can pass the 'edit' option to the form.
     * This allows us to set the filename field of the form to be optional, as an image
     * will already have been uploaded when creating the object.
     */
    public function executeEdit(sfWebRequest $request)
    {
        $this->sf_image_pool_image = $this->getRoute()->getObject();
        $this->form = $this->configuration->getForm($this->sf_image_pool_image, array('edit' => true));
    }
    
    /**
     * A generic upload action for creating new images and saving them
     * with tags, not not associating with any object.
     * 
     * A straight forward add to pool action. Written to allow integration with other plugins
     * or uploading contexts which don't involve the admin gen and an sfImagePoolImage form.
     * Handles multiple files, all with same tags. Good for WYSIWYG editor integration.
     * 
     * Checks for a 'tag' field name and uses this to add tags. Can be
     * a CSV list or a single string.
     * 
     * Only works with POST data, doesn't aim to replace any data (PUT).
     */
    public function executeUpload(sfWebRequest $request)
    {
      $this->forward404Unless($request->isMethod('post'));
      
      // process each upload one by one
      foreach($request->getFiles() as $upload)
      {
        // first do checks for standard php upload errors, as we probably won't have
        // an uploaded file to deal with at all, so need the correct error message.
        $errors = sfImagePoolUtil::getUploadErrors($upload);
        
        // spit out any errors we've got and halt right here.
        if(count($errors))
        {
          $message = sprintf('<ul><li>%s</li></ul>', implode('</li><li>', $errors));
          return $this->renderText($message);  
        }
         
        sfImagePoolUtil::createImageFromUpload($upload, $tags);
      }
      
      return sfView::NONE;
    }
    
    /**
     * For uploading a custom crop for an image.
     */
    public function executeUploadCrop(sfWebRequest $request)
    {
        $image = $this->getRoute()->getObject();
        
        $crop = new sfImagePoolCrop();
        $crop['Image'] = $image;
        
        // upload an image, get its dimensions and then put into the 'crop' folder
        // with the same name as the sfImagePoolImage assigned in the route, with the full
        // path according to its dimensions (e.g. crop/300/200/filename.png)
        // This is all that is needed to activate a custom crop for a specific size of a certain file.
        $form = new sfImagePoolCropForm($crop);
        
        if($request->isMethod('post'))
        {
            $form->bind($request->getParameter($form->getName()), $request->getFiles($form->getName()));
                        
            if($form->isValid())
            {
                try
                {
                    $form->save();
                }
                // if user uploaded a new crop but one exists in the DB Doctrine
                // will complain a crop row should be unique. BUT, we've updated the
                // file so don't need to save new object, so don't do anything
                // if the constraint fails - let it.
                catch(Doctrine_Connection_Mysql_Exception $e) { }
                
                $path_to_file = $form->getPathToUpload();
                $image_info   = getimagesize($path_to_file);
                
                list($width, $height, $type, $attr) = $image_info;
                
                $this->getUser()->setFlash('notice', sprintf('%sx%s crop was uploaded successfully.', $width, $height));
                $this->getUser()->setFlash('image', $image);
                $this->getUser()->setFlash('uploaded_file', $path_to_file);
                $this->getUser()->setFlash('uploaded_file_dimensions', sprintf('%sx%s', $width, $height));
                
                $this->redirect('@sf_image_pool_image');
            }
        }
        
        $this->setVar('form', $form);
        $this->setVar('image', $image);
        
        return sfView::SUCCESS;
    }
    
    /**
     * Delete all the files on the filesystem IF the image isn't being used. 
     * Presents list of places the image is being used for review.
     */
    public function executeDelete(sfWebRequest $request)
    {
        $image = $this->getRoute()->getObject();
        
        if($models = $image->isUsed())
        {
            $this->setVar('image', $image);
            $this->setVar('models', $models);
        }
        else
        {
            parent::executeDelete($request);
        }
    } 

    /**
     * Delete an image that is being used. User will have had to review
     * its usage and confirm it should be deleted.
     */
    public function executeDeleteUsed(sfWebRequest $request)
    {
        parent::executeDelete($request);
    }
    
    /**
     * AJAX powered pagination
     *
     * @return void
     * @author Ben Lancaster
     * @author Jo Carter <jocarter@holler.co.uk>
     **/
    public function executeChooser(sfWebRequest $request)
    {
      sfConfig::set('sf_web_debug', false);
      
      // Is there a potential taggable object to restrict by?
      $class = ($request->hasParameter('class')       ? $request->getParameter('class')     : null);
      $class_id = ($request->hasParameter('class_id') ? $request->getParameter('class_id')  : null);
      
      if ($class && $class_id) 
      {
        $object = Doctrine_Core::getTable($class)->findOneById($class_id);
      }
      else $object = null;
      
      // Or a tag set? Added for MooEditable
      $tag = ($request->hasParameter('tag')           ? $request->getParameter('tag')       : null);
      
      $per_page = sfConfig::get('app_sf_image_pool_chooser_per_page', 24);
      $pager    = sfImagePoolImageTable::getInstance()->getPager($per_page, $request->getParameter('page', 1), $object, $tag);
      
      $vars = array(
          'paginationId'  => 'pagination',
          'pager'         => $pager,
          'object'        => $object,
          'id'            => $request->getParameter('chooser_id'),
          'tag'           => $tag,          // Added for MooEditable
          'multiple'      => ($request->getParameter('multiple', false) === 'true'),
      );
      
      return $this->renderPartial('sfImagePoolAdmin/chooser_pagination', $vars);
    }
    
    
    /**
     * iFrame powered upload for the AJAX image chooser (to avoid form within form difficulties)
     * 
     * @author Jo Carter
     * @param sfWebRequest $request
     */
    public function executeChooserUpload(sfWebRequest $request)
    {
      $image = new sfImagePoolImage();
      $this->form = $this->configuration->getForm(null, array('edit' => false));
      $this->form->disableLocalCSRFProtection();
      $this->success = false;
      
      $tag = ($request->hasParameter('tag') ? $request->getParameter('tag') : null);
      if ($tag) $this->form->setDefault('image_tags', $tag);
      
      if ($request->isMethod(sfWebRequest::POST) && $request->hasParameter('sf_image_pool_image'))
      {
        $this->form->bind($request->getParameter('sf_image_pool_image'), $request->getFiles('sf_image_pool_image'));
        
        if ($this->form->isValid())
        {
          $this->form->save();
          $this->success = true;
        }
      }
      
      sfConfig::set('sf_web_debug', false);
      $this->setLayout(false);
    }
}
