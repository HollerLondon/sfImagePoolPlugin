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
function sf_image_pool_image($invoker, $dimensions = 200, $method = 'crop', $attributes = array(), $absolute = false)
{
  trigger_error("sf_image_pool_image from sfImagePoolHelper is deprecated, use the pool_image_tag from ImagePoolHelper",E_USER_DEPRECATED);
  call_user_func_array('pool_image_tag', func_get_args());
}


function sf_image_pool_image_url($image, $dimensions = 200, $method = 'crop', $absolute = false)
{
  trigger_error("sf_image_pool_image_url from sfImagePoolHelper is deprecated, use the pool_image_uri from ImagePoolHelper",E_USER_DEPRECATED);
  call_user_func_array('pool_image_uri', func_get_args());
}