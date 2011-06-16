<?php
class sfImagePoolImageCollection extends Doctrine_Collection
{
  private $_featuredImage = false;

  public function getFeatured()
  {
    return $this->getFeaturedImage();
  }

  public function getFeaturedImage()
  {
    return
      $this->_featuredImage instanceof sfImagePoolImage
      && $this->contains($this->_featuredImage->getPrimaryKey())
        ? $this->_featuredImage
        : $this->getFirst();
  }
  
  public function setFeaturedImage(sfImagePoolImage $image)
  {
    $this->_featuredImage = $image;
  }
  
  public function save(Doctrine_Connection $conn = null, $processDiff = true)
  {
    return $this;
  }
  
  /**
   * return @array
   */
  public function getUnfeatured()
  {
      $data = $this->getData();
      $featured = $this->getFeaturedImage();
      
      foreach($data as $index => $image)
      {
          if($image->getPrimaryKey() == $featured->getPrimaryKey())
          {
              unset($data[$index]);
          }
      }
      
      return $data;
  }
}