<?php
/**
 *
 */
class ImageTransform
{
  private $workingImageResource;
  private $pathToSourceFile;
  private $sourceFileExtension;

  const JPEG_QUALITY = 90;

  static function invertColour($colour)
  {
    if(!preg_match("/[0-9a-f]{6}/",$colour))
    {
      throw new Exception("Not a valid hexidecimal colour-code");
    }

    $r = 255 - hexdec(substr($colour,0,2));
    $g = 255 - hexdec(substr($colour,2,2));
    $b = 255 - hexdec(substr($colour,4,2));

    return sprintf('%02x%02x%02x',$r,$g,$b);
  }

  /**
   * Set the path to the file to be cropped.
   * NOTE - the file will be overwritten
   *
   * @param string $pathToSourceFile Image file that should be modified
   */
  public function __construct($pathToSourceFile = '', $mime = null)
  {
    if(!file_exists($pathToSourceFile))
    {
      throw new Exception("$pathToSourceFile does not exist");
    }
    
    if(!$mime)
    {
      throw new Exception("Mime type '$mime' is not valid");
    }

    $this->pathToSourceFile = $pathToSourceFile;
    $this->type = $mime;
    
    $this->createWorkingImageResource($pathToSourceFile);
  }

  /**
   * Create a GD image resource from the given path using the correct
   * GD function according to the given file's type.
   */
  private function createWorkingImageResource($pathToSourceFile)
  {
    switch($this->type)
    {
      case 'image/jpg':
      case 'image/jpeg':
        $this->workingImageResource = imagecreatefromjpeg($pathToSourceFile);
        break;

      case 'image/png':
        $this->workingImageResource = imagecreatefrompng($pathToSourceFile);
        break;
    }
  }

  /**
   *
   */
  private function getWorkingImageResource()
  {
    return $this->workingImageResource;
  }

  /**
   *
   */
  private function setWorkingImageResource($image)
  {
   if(
    $image !== $this->getWorkingImageResource()
    && is_resource($this->getWorkingImageResource())
   ){
    imagedestroy($this->getWorkingImageResource());
   }
   $this->workingImageResource = $image;
  }

  /**
   * Fetch the image width
   * @return integer
   */
  private function getWorkingImageWidth()
  {
    return imagesx($this->workingImageResource);
  }

  /**
   * Fetch the image height
   * @return integer
   */
  private function getWorkingImageHeight()
  {
    return imagesy($this->workingImageResource);
  }  

  /**
   * Resize an image.
   *
   * If both width and height are given, the image will be scaled to match.
   *
   * @param integer $width
   * @param integer $height
   */
  public function resize($newWidth, $newHeight)
  {
    $imageIn = $this->getWorkingImageResource();
    $imageOut = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled(
     $imageOut,            // resource dst_im
     $imageIn,            // resource src_im
     0,                // int dst_x
     0,                // int dst_y
     0,                // int src_x
     0,                // int src_y
     $newWidth,            // int dst_w
     $newHeight,           // int dst_h
     $this->getWorkingImageWidth(),  // int src_w
     $this->getWorkingImageHeight()  // int src_h
    );

    $this->setWorkingImageResource($imageOut);
  }

  /**
   * Perform the crop on the file passed to the constructor
   *
   * @param integer $w Crop width
   * @param integer $h Crop height
   * @param integer $x Crop start horizontal position
   * @param integer $y Crop start vertical position
   * @return void
   */
  public function crop($w, $h, $x = 0, $y = 0)
  {
    if(!is_numeric($x) || !is_numeric($y) || !is_numeric($w) || !is_numeric($h))
    {
      throw new Exception('One of the parameters was not numeric');
    }

    $imageIn = $this->getWorkingImageResource();
    $imageOut = imagecreatetruecolor($w, $h);

    imagecopyresampled($imageOut, $imageIn, 0, 0, $x, $y, $w, $h, $w, $h);

    $this->setWorkingImageResource($imageOut);
  }
  
  /**
   * Size image to match a canvas.
   *
   * 1) If the image is not tall enough then it's height will be scaled (and width
   * to maintain the aspect ratio).
   *
   * 2) If the image is still not wide enough, the it is enlarged to fit,
   * the aspect being preserved, so the height will increased here too.
   */
  public function fit($desiredWidth, $desiredHeight,$crop=true)
  {
    $cropX = 0;
    $cropY = 0;
    
    $imageWidth  = $this->getWorkingImageWidth();
    $imageHeight = $this->getWorkingImageHeight();
    $currentRatio = $imageHeight / $imageWidth;
    $desiredRatio = $desiredHeight / $desiredWidth;

    // simple scale
    if($currentRatio == $desiredRatio)
    {
      $this->resize($desiredWidth, $desiredHeight);
      return true;
    }

    // width priority - maybe crop the height if needed
    if($currentRatio > $desiredRatio)
    {
      $destWidth = $desiredWidth;
      $destHeight = ceil($imageHeight * ($desiredWidth / $imageWidth));
      
      // set cropY so that the crop area occurs in the vertical centre
      $cropY = ceil(($destHeight - $desiredHeight) / 2);
    }

    // height priority - maybe crop the width if needed
    elseif($currentRatio < $desiredRatio)
    {
      $destHeight = $desiredHeight;
      $destWidth = floor($imageWidth * ($desiredHeight / $imageHeight));

      // set cropX so that the crop area occurs in the horizontal centre
      $cropX = ceil(($destWidth - $desiredWidth) / 2);
    }

    $this->resize($destWidth, $destHeight);
    if($crop)
    {
     $this->crop($desiredWidth, $desiredHeight, $cropX, $cropY);
    }
  }  

  public function fitToLongestEdge($edge)
  {
   $sWidth  = $this->getWorkingImageWidth();
   $sHeight = $this->getWorkingImageHeight();
   
   if ($sWidth > $sHeight && $sWidth > $edge)
   {
    $width = $edge;
    $height = floor($sHeight * ($edge / $sWidth));
   }
   elseif($sHeight > $sWidth && $sHeight > $edge)
   {
    $height = $edge;
    $width  = floor($sWidth * ($edge / $sHeight));
   }
   elseif($sHeight == $sWidth && $sHeight > $edge)
   {
    $width = $height = $edge;
   }
   else
   {
    return;
   }

   $imageOut = imagecreatetruecolor($width, $height);

   imagecopyresampled(
    $imageOut,
    $this->getWorkingImageResource(),
    0,
    0,
    0,
    0,
    $width,
    $height,
    $this->getWorkingImageWidth(),
    $this->getWorkingImageHeight()
   );

   $this->setWorkingImageResource($imageOut);
  }

  /**
   * Equivalent to adding a solid-colour Photoshop layer over an image and
   * setting the blend mode to "Multiply"
   *
   * @param resource GD Image resource from imagecreate* function
   * @param string $colour Hex-value for the colour to use as the overlay
   * @return resource
   **/
  function multiplyColour($colour, $greyscale = false)
  {
    if(function_exists('imagefilter'))
    {
      $inverse = self::invertColour($colour);
      $r = hexdec(substr($inverse,0,2));
      $g = hexdec(substr($inverse,2,2));
      $b = hexdec(substr($inverse,4,2));

      $gd = $this->getWorkingImageResource();
      imagefilter($gd,IMG_FILTER_NEGATE);
      if($greyscale)
      {
        $this->makeGreyscale();
      }
      imagefilter($gd,IMG_FILTER_COLORIZE,$r,$g,$b);
      imagefilter($gd,IMG_FILTER_NEGATE);
      $this->setWorkingImageResource($gd);
    }
  }

  /**
   * Get rid of the colours in this image
   *
   * @return void
   * @author Ben Lancaster
   **/
  public function makeGreyscale($gd = false)
  {
    if(function_exists('imagefilter'))
    {
      $gd = is_resource($gd) ? $gd : $this->getWorkingImageResource();
      imagefilter($gd,IMG_FILTER_GRAYSCALE);
    }
  }

  /**
   * Adds diagonal stripes to the image
   **/
  public function addStripes()
  {
    $gd = $this->getWorkingImageResource();
    
    $c = array(
      sfConfig::get('sf_web_dir'),
      'images',
      'stripes.png',
    );
    
    $stripes = implode(DIRECTORY_SEPARATOR,$c);
    
    $s = imagecreatefrompng($stripes);
    imagecopy(
      $this->getWorkingImageResource($gd),
      $s,
      0,
      0,
      0,
      0,
      imagesx($s),
      imagesy($s)
    );
  }

  /**
   * Sharpen an image
   * 
   * Algoithm nicked from maettig.com. Requires PHP to be allocated lots of memory. 
   *
   * @param imageresource $image
   * @return void
   */
  function sharpen($amount = 20, $radius = 0.5, $threshold = 2)
  {
   $img = $this->getWorkingImageResource();
   
   if ($amount > 500)
   {
    $amount = 500; 
   }
   if ($radius > 50)
   {
    $radius = 50; 
   }
   if ($threshold > 255)
   {
    $threshold = 255; 
   }

   $amount   = $amount * 0.016; 
   $radius   = abs(round($radius * 2)); 
   $w     = $this->getWorkingImageWidth();
   $h     = $this->getWorkingImageHeight();

   $imgCanvas = imagecreatetruecolor($w, $h); 
   $imgBlur  = imagecreatetruecolor($w, $h); 

   if (function_exists('imageconvolution'))
   { 
    $matrix = array( 
     array( 1, 2, 1 ), 
     array( 2, 4, 2 ), 
     array( 1, 2, 1 ) 
    ); 

    imagecopy ($imgBlur, $img, 0, 0, 0, 0, $w, $h); 
    imageconvolution($imgBlur, $matrix, 16, 0); 
   } 
   else
   { 
    for ($i = 0; $i < $radius; $i++)
    { 
     imagecopy ($imgBlur, $img, 0, 0, 1, 0, $w - 1, $h); // left 
     imagecopymerge ($imgBlur, $img, 1, 0, 0, 0, $w, $h, 50); // right 
     imagecopymerge ($imgBlur, $img, 0, 0, 0, 0, $w, $h, 50); // center 
     imagecopy ($imgCanvas, $imgBlur, 0, 0, 0, 0, $w, $h); 
     imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 33.33333 ); // up 
     imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 25); // down 
    } 
   } 

   // Calculate the difference between the blurred pixels and the original 
   // and set the pixels 
   // each row
   for ($x = 0; $x < $w-1; $x++)
   { 
    // each pixel 
    for ($y = 0; $y < $h; $y++)
    { 
     if($threshold > 0)
     { 
      $rgbOrig = ImageColorAt($img, $x, $y); 
      $rOrig = (($rgbOrig >> 16) & 0xFF); 
      $gOrig = (($rgbOrig >> 8) & 0xFF); 
      $bOrig = ($rgbOrig & 0xFF); 

      $rgbBlur = ImageColorAt($imgBlur, $x, $y); 

      $rBlur = (($rgbBlur >> 16) & 0xFF); 
      $gBlur = (($rgbBlur >> 8) & 0xFF); 
      $bBlur = ($rgbBlur & 0xFF); 

      // When the masked pixels differ less from the original 
      // than the threshold specifies, they are set to their original value. 
      if(abs($rOrig - $rBlur) >= $threshold) 
      {
       $rNew = max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig));
      }
      else
      {
       $rNew = $rOrig;
      }

      if(abs($gOrig - $gBlur) >= $threshold)
      {
       $gNew = max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig));
      }
      else
      {
       $gNew = $gOrig;
      }

      if(abs($bOrig - $bBlur) >= $threshold)
      {
       $bNew = max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig));
      }
      else
      {
       $bNew = $bOrig;
      }

      if (($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew))
      { 
       $pixCol = ImageColorAllocate($img, $rNew, $gNew, $bNew); 
       ImageSetPixel($img, $x, $y, $pixCol); 
      }
     }
     else
     {
      $rgbOrig = ImageColorAt($img, $x, $y); 
      $rOrig  = (($rgbOrig >> 16) & 0xFF); 
      $gOrig  = (($rgbOrig >> 8) & 0xFF); 
      $bOrig  = ($rgbOrig & 0xFF); 
      $rgbBlur = ImageColorAt($imgBlur, $x, $y); 
      $rBlur  = (($rgbBlur >> 16) & 0xFF); 
      $gBlur  = (($rgbBlur >> 8) & 0xFF); 
      $bBlur  = ($rgbBlur & 0xFF); 

      $rNew = ($amount * ($rOrig - $rBlur)) + $rOrig; 
      if($rNew>255)
      {
       $rNew = 255;
      } 
      elseif($rNew<0)
      {
       $rNew = 0;
      } 
      $gNew = ($amount * ($gOrig - $gBlur)) + $gOrig; 
      if($gNew>255)
      {
       $gNew=255;
      } 
      elseif($gNew<0)
      {
       $gNew=0;
      } 
      $bNew = ($amount * ($bOrig - $bBlur)) + $bOrig; 
      if($bNew>255)
      {
       $bNew=255;
      } 
      elseif($bNew<0)
      {
       $bNew=0;
      } 
      $rgbNew = ($rNew << 16) + ($gNew <<8) + $bNew; 
      ImageSetPixel($img, $x, $y, $rgbNew); 
     }
    } 
   } 
   
   $this->setWorkingImageResource($img);
   imagedestroy($imgCanvas);
   imagedestroy($imgBlur);
  } 
  
  /**
   * Write the transformed file out (overwriting source image)
   *
   * @return boolean
   */
  public function save($pathToOutputFile)
  {
    switch($this->type)
    {
      case 'image/jpg':
      case 'image/jpeg':
        return imagejpeg($this->getWorkingImageResource(), $pathToOutputFile, self::JPEG_QUALITY);

      case 'image/png':
        return imagepng($this->getWorkingImageResource(), $pathToOutputFile);
    }
  }
  
  function __destruct()
  {
   imagedestroy($this->getWorkingImageResource());
  }
}

