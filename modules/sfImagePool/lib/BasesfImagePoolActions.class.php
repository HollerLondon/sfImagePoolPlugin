<?php
class BasesfImagePoolActions extends sfActions
{
    
    /**
     * Generate, cache and display the given image
     */
    public function executeImage(sfWebRequest $request)
    {
        $sf_pool_image = $this->getRoute()->getObject();

        $thumb_method  = $request->getParameter('method');
        $width         = $request->getParameter('width');
        $height        = $request->getParameter('height');
        
        $cache_options = sfConfig::get('app_sf_image_pool_cache',array());
        $class         = $cache_options['class'];
        
        $cache = new $class($sf_pool_image,$cache_options);
        
        try
        {
          // check file exists on the filesystem
          if(!file_exists($sf_pool_image->getPathToOriginalFile()))
          {
            throw new sfImagePoolException(sprintf('%s does not exist', $sf_pool_image->getPathToOriginalFile()));
          }
          
          // create thumbnail
          $resizer = new sfImagePoolResizer($sf_pool_image, $thumb_method, $width, $height);
          
          $resizer->sharpen = sfConfig::get('app_sf_image_pool_sharpen',true);

          $thumb   = $resizer->save($cache->getDestination());
        
          // get thumbnail data and spit out
          $image_data = $thumb->toString();
          $response   = $this->getResponse();
        
          // set headers so when image is requested again, if it exists
          // on the filesystem it'll just be fetched from the browser cache.
          if($cache->sendCachingHttpHeaders())
          {
            $response->setContentType('image/jpeg');

            $response->addCacheControlHttpHeader('public');
            $response->addCacheControlHttpHeader('max_age',$cache->getLifetime());
          
            $response->setHttpHeader('Last-Modified', date('D, j M Y, H:i:s'));
            $response->setHttpHeader('Expires', date('D, j M Y, H:i:s', strtotime(sprintf('+ %u second', $cache['lifetime']))));
            $response->setHttpHeader('Content-Length', strlen($image_data));
          }
          
          $response->setHttpHeader('X-Is-Cached', 'no');

          sfConfig::set('sf_web_debug', false);
          
          $cache->commit();
          
          return $this->renderText($image_data);
        }

        // thumbnail could not be generated so let's spit out a thumbnail instead 
        catch(sfImagePoolException $e)
        {
          if(sfConfig::get('app_sf_image_pool_placeholders',false))
          {
            $dest = sfConfig::get('app_sf_image_pool_use_placeholdit', false)
              ? sprintf('http://placehold.it/%ux%u',$width,$height,urlencode($e->getMessage()))
              : sprintf('@image?width=%s&height=%s&filename=%s&method=%s', $width, $height, 'placeholder.jpg', $thumb_method);
            
            $this->redirect($dest, 302);
          }
          else
          {
            throw $e;
          }
        }
    }       
}
?>