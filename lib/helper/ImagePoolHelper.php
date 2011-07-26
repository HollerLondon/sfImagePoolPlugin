<?php
/**
 * Output image of given size for an sfImagePoolImage.
 *
 * @param Mixed $image Either sfImagePoolImage instance or array
 * @param string $dimensions e.g. 'crop=200x150' or '200' for fit to 200 width (scale is default)
 * @param array $attributes
 * 
 * @return string IMG tag including attributes
 */
function pool_image_tag($invoker, $dimensions = 200, $method = 'crop', $attributes = array(), $absolute = false)
{
  // remove Symfony escaping if applied
  if ($invoker instanceof sfOutputEscaper)
  {
      $invoker = $invoker->getRawValue();
  }
  
  // attempt to fetch associated sfImagePoolImage
  $image = ($invoker instanceof sfImagePoolImage)
      ? $invoker
      : $invoker->getFeaturedImage();

  if (is_array($dimensions))
  {
    $w  = $dimensions[0];
    $h  = $dimensions[1];
  }
  // parse dimensions string for width and height
  else if (strpos(strtolower($dimensions), 'x') !== false)
  {
      list($w, $h) = explode('x', $dimensions);
  }
  // set width and height to the same as $dimensions default
  // this *might* need changing if it produces inaccurate results with 'scale'
  else
  {
      $h = $w = $dimensions;
  }

  $attributes = _sf_image_pool_build_attrs($image, array($w,$h), $method, $attributes);

  return image_tag(pool_image_uri($image,array($w,$h),$method,$absolute),$attributes);
}

function _sf_image_pool_build_attrs($invoker, $dimensions, $method, $attributes = array())
{
  $attributes = _parse_attributes($attributes);

  if (is_array($dimensions))
  {
    $w  = $dimensions[0];
    $h  = $dimensions[1];
  }
  // parse dimensions string for width and height
  else if (strpos(strtolower($dimensions), 'x') !== false)
  {
      list($w, $h) = explode('x', $dimensions);
  }
  // set width and height to the same as $dimensions default
  // this *might* need changing if it produces inaccurate results with 'scale'
  else
  {
    $h = $w = $dimensions;
  }

  // set alt tag if not already set. if image's caption is empty, use original filename.
  if (!isset($attributes['alt']))
  {
    $attributes['alt'] = $invoker['caption'];
  }
  
  // set title tag if not already set.
  // ONLY in the backend - if image's caption is empty, use original filename.
  if (!isset($attributes['title']))
  {
    if ('backend' == sfConfig::get('sf_app')) 
    {
      $attributes['title'] = $invoker['original_filename'];
    }
  }
  // Don't show any empty titles
  if (isset($attributes['title']) && empty($attributes['title'])) unset($attributes['title']);

  // we can specify width and height IF we're dealing with a crop,
  // as when using scale, a fit happens and the precise dimensions of the resulting
  // image aren't necessarily what were specified.
  if ($method == 'crop')
  {
    $attributes['width']  = $w;
    $attributes['height'] = $h;
  }

  return $attributes;
}

function pool_image_uri($image, $dimensions = 200, $method = 'crop', $absolute = false)
{
  // remove Symfony escaping if applied
  if ($image instanceof sfOutputEscaper)
  {
      $image = $image->getRawValue();
  }
  
  $offsite = false;
  
  if (is_array($dimensions))
  {
    $width  = $dimensions[0];
    $height = $dimensions[1];
  }
  // parse dimensions string for width and height
  else if (strpos(strtolower($dimensions), 'x') !== false)
  {
      list($width, $height) = explode('x', $dimensions);
  }
  // set width and height to the same as $dimensions default
  // this *might* need changing if it produces inaccurate results with 'scale'
  else
  {
      $height = $width = $dimensions;
  }
  
  $cache_options = sfConfig::get('app_sf_image_pool_cache');
  $class = $cache_options['class'];

  // If remote and remote uri set, plus image exists
  if ($class::IS_REMOTE && !empty($cache_options['off_site_uri']) && $image) 
  {
    // check whether crop exists - if it doesn't business as usual
    $is_crop = ('crop' == $method);
    $crop = sfImagePoolCropTable::getInstance()->findCrop($image, $width, $height, $is_crop, $class::CROP_IDENTIFIER);
    
    if ($crop)
    {
      $absolute = false;
      $offsite = true;
    }
  }
  
  if (!function_exists('url_for'))
  {
    sfApplicationConfiguration::getActive()->loadHelpers(array('Url'));
  }
  
  // If we have an empty sfImagePool instance (ie. no image) then output a placeholder if set in config to do so
  if (!$image['filename'])
  {
    if (sfConfig::get('app_sf_image_pool_placeholders', false))
    {
      if (sfConfig::get('app_sf_image_pool_use_placeholdit', false))
      {
        $url = sprintf('http://placehold.it/%ux%u&text=%s', $width, $height, urlencode(sfConfig::get('app_sf_image_pool_placeholdit_text', ' ')));
      }
      else
      {
        // If offsite then should have cached placeholder too
        if ($class::IS_REMOTE && !empty($cache_options['off_site_uri']))
        {
          $absolute = false;
          $offsite = true;
        }
        
        $url = url_for(sprintf('@image?width=%s&height=%s&filename=%s&method=%s', $width, $height, sfImagePoolImage::DEFAULT_FILENAME, $method), $absolute);
      }
    }
    else return false; // No image, no placeholder
  }
  else
  {
    $url = url_for(sprintf('@image?width=%s&height=%s&filename=%s&method=%s', $width, $height, $image['filename'], $method), $absolute);
  }

  // Do we want to remove the controller filename? It's good to have this option independent of the global Symfony
  // setting as we may be generating URLs to insert into the db and which should not have any controller referenced.
  if (!sfConfig::get('app_sf_image_pool_use_script_name', false) || $offsite)
  {
    $url = preg_replace('%\w+(_dev)?\.php/%', '', $url);
  }
  
  // If offsite - then replace local image-pool folder with offsite URL (naming convention for offsite should mirror 
  // folder structure for local)
  if ($offsite)
  {
    $url = str_replace(sfImagePoolPluginConfiguration::getBaseUrl(), $cache_options['off_site_uri'], $url);
  }
  
  return $url;
}