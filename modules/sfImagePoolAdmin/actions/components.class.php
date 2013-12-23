<?php 
class sfImagePoolAdminComponents extends sfComponents
{
  public function executeSizes(sfWebRequest $request)
  {
    $image = $this->getVar('image');
    
    $rawCrops    = sfImagePoolCropTable::getInstance()->findBySfImageIdAndIsCrop($image->id, true);
    $this->crops = array();
    
    foreach ($rawCrops as $crop)
    {
      if ($crop->is_crop) 
      {
        $idx = $crop->width . 'x' . $crop->height;
        $this->crops[$idx] = $crop;
      }
    }
    
    if (empty($this->crops)) return sfView::NONE;
  }
}
