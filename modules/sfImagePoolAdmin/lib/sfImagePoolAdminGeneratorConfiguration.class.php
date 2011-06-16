<?php

/**
 * sfImagePoolAdmin module configuration.
 *
 * @package    olmecatahonasociety
 * @subpackage sfImagePoolAdmin
 * @author     Your name here
 * @version    SVN: $Id: configuration.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class sfImagePoolAdminGeneratorConfiguration extends BaseSfImagePoolAdminGeneratorConfiguration
{
  public function getFilterDisplay()
  {
    $fields = $this->checkTaggingField(parent::getFilterDisplay(),'tag');
    return $fields;
  }
  
  public function getEditDisplay()
  {
    $fields = $this->checkTaggingField(parent::getEditDisplay(),'_tagging');
    return $fields;
  }

  public function getNewDisplay()
  {
    $fields = $this->checkTaggingField(parent::getNewDisplay(),'_tagging');
    return $fields;
  }
  
  protected function checkTaggingField($fields,$tagging_field)
  {
    $im     = new sfImagePoolImage;
    $k      = array_search($tagging_field,$fields);
    
    if($k !== false && !$im->option('tagging'))
    {
      unset($fields[$k]);
    }
    return $fields;
  }
  
}
