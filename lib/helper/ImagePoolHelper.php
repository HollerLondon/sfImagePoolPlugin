<?php
/**
 * Output image of given size for an sfImagePoolImage.
 *
 * @param mixed $invoker Model or sfImagePool image
 * @param string $dimensions e.g. 'crop=200x150' or '200' for fit to 200 width (scale is default)
 * @param mixed $options either string or array, e.g. array('method' => 'scale', 'require_size' => true)
 * @param string $attributes HTML attributes, such as width, height and alt
 * @param boolean $absolute return absolute URL for an image
 * @return string
 * @author Jimmy Wong
 */
function pool_image_tag($invoker, $dimensions = 200, $options = 'crop', $attributes = array(), $absolute = false)
{
  if (is_null($invoker)) return;

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

  if (is_array($options))
  {
    $method = array_key_exists('method',$options) ? $options['method'] : 'crop';
  }
  else
  {
    $method   = $options;
    $options  = array();
  }

  $pool_image_uri = pool_image_uri($image, array($w,$h), $method, $absolute);

  $options['require_size'] = array_key_exists('require_size',$options)
    ? $options['require_size']
    : sfConfig::get('app_sf_image_pool_require_size', true);

  // We need the actual image dimensions so the space is correct on the page
  if ($image && array_key_exists('require_size',$options) && true == $options['require_size'])
  {
    // Only if we're scaling it - get the image size
    if ('scale' == $method)
    {
      list($attributes['width'], $attributes['height']) = sfImagePoolUtil::calculateWidthAndHeight($image, $w, $h);
    }
    else
    {
      $attributes['width'] = $w;
      $attributes['height'] = $h;
    }
  }

  $attributes = _sf_image_pool_build_attrs($image, $options['require_size'] ? array($w,$h) : false, $method, $attributes);

  return image_tag($pool_image_uri,$attributes);
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
  if ($method == 'crop' && $w && $h)
  {
    $attributes['width']  = $w;
    $attributes['height'] = $h;
  }

  return $attributes;
}

/**
 * Get the URL for the original uploaded file
 * 
 * @NOTE: This just links to base file so not got controller in - does do a replace if offsite now
 * 
 * @param sfImagePoolImage | string $image
 * @param boolean $absolute
 * @return string
 */
function pool_image_source_uri($image, $absolute = false)
{
  if ($image instanceof sfOutputEscaper)
  {
    $image = $image->getRawValue();
  }

  $filename = $image instanceof sfImagePoolImage ? $image['filename'] : $image;
  
  if (!$filename) return null;
  
  // If we are on a secure page we want to use the ssl option to avoid security warnings
  $ssl = sfContext::getInstance()->getRequest()->isSecure();
  $off_site_index = ($ssl ? 'off_site_ssl_uri' : 'off_site_uri');
  
  // Check if offsite
  $cache_options = sfConfig::get('app_sf_image_pool_cache');
  $class         = $cache_options['class'];
  
  if ($class::IS_REMOTE && !empty($cache_options[$off_site_index])) $offsite = true;
  else $offsite = false;

  if (!$offsite) 
  {
    $url = _compute_public_path($filename, sfImagePoolPluginConfiguration::getBaseUrl(), $absolute, false);
  }
  else 
  {
    $url = sprintf('%s/%s', $cache_options[$off_site_index], $filename);
  }
  
  return $url;
}

/**
 * Get URL for the image pool file
 * 
 * @param sfImagePoolImage | string $image
 * @param string $dimensions
 * @param string $method
 * @param boolean $absolute
 * @return string
 */
function pool_image_uri($image, $dimensions = 200, $method = 'crop', $absolute = false)
{
  // remove Symfony escaping if applied
  if ($image instanceof sfOutputEscaper)
  {
    $image = $image->getRawValue();
  }

  $offsite = false;

  if ($dimensions == 'original' || (is_array($dimensions) && 'original' == $dimensions[0]))
  {
    return pool_image_source_uri($image, $absolute);
  }

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

  // If we are on a secure page we want to use the ssl option to avoid security warnings
  $ssl = sfContext::getInstance()->getRequest()->isSecure();
  $off_site_index = ($ssl ? 'off_site_ssl_uri' : 'off_site_uri');

  // If remote and remote uri set, plus image exists
  if ($class::IS_REMOTE && !empty($cache_options[$off_site_index]) && $image)
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
  if ($image instanceof sfImagePoolImage && !$image['filename'])
  {
    if (sfConfig::get('app_sf_image_pool_placeholders', false))
    {
      if (sfConfig::get('app_sf_image_pool_use_placeholdit', false))
      {
        $url = sprintf('http://placehold.it/%ux%u&text=%s', $width, $height, urlencode(sfConfig::get('app_sf_image_pool_placeholdit_text',$width.'x'.$height)));
      }
      else
      {
        // If offsite then should have cached placeholder too - check whether created as crop too
        if ($class::IS_REMOTE && !empty($cache_options[$off_site_index]))
        {
          $is_crop = ('crop' == $method);
          $crop = sfImagePoolCropTable::getInstance()->findCrop(sfImagePoolImage::DEFAULT_FILENAME, $width, $height, $is_crop, $class::CROP_IDENTIFIER);

          if ($crop)
          {
            $absolute = false;
            $offsite = true;
          }
        }
        $url = url_for(sprintf('@image?width=%s&height=%s&filename=%s&method=%s', $width, $height, sfImagePoolImage::DEFAULT_FILENAME, $method), $absolute);
      }
    }
    else return false; // No image, no placeholder
  }
  else
  {
    $filename = $image instanceof sfImagePoolImage ? $image['filename'] : $image;

    // No image associated with this object, use placehold.it instead?
    if (!$filename && sfConfig::get('app_sf_image_pool_use_placeholdit', false))
    {
      return sprintf('http://placehold.it/%ux%u&text=%s',$width, $height, urlencode(sfConfig::get('app_sf_image_pool_placeholdit_text', $width.'x'.$height)));
    }

    // Or, if not placehold.it and placeholders is enabled, let's spit one out.
    else if (!$filename && sfConfig::get('app_sf_image_pool_placeholders', true))
    {
      $filename = 'placeholder.jpg';
    }

    // but if placeholders isn't switched on then output nothing.
    else if (!$filename)
    {
      return false;
    }

    $url = url_for(sprintf('@image?width=%s&height=%s&filename=%s&method=%s',$width, $height, $filename, $method), $absolute);
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
    $url = str_replace(sfImagePoolPluginConfiguration::getBaseUrl(), $cache_options[$off_site_index], $url);
  }

  return $url;
}