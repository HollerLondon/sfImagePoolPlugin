<?php
/**
 * Base class for all Image Pool actions
 *
 * @package symfony
 * @subpackage sfImagePoolPlugin
 * @author Ben Lancaster
 */
class BasesfImagePoolActions extends sfActions
{
    /**
     * Generate, cache and display the given image
     */
    public function executeImage(sfWebRequest $request)
    {
        sfConfig::set('sf_web_debug', false);
      
        $sf_pool_image = $this->getRoute()->getObject();
        $response      = $this->getResponse();

        $thumb_method  = $request->getParameter('method');
        $width         = $request->getParameter('width');
        $height        = $request->getParameter('height');
        
        try
        {
          // check file exists on the filesystem
          if (!file_exists($sf_pool_image->getPathToOriginalFile()))
          {
            throw new sfImagePoolException(sprintf('%s does not exist', $sf_pool_image->getPathToOriginalFile()));
          }
          
          // create resizer and cache
          $resizer = new sfImagePoolResizer($sf_pool_image, $thumb_method, $width, $height);
          $cache   = sfImagePoolCache::getInstance($sf_pool_image, array(), $resizer->getParams());
          
          // set headers so when image is requested again, if it exists
          // on the filesystem it'll just be fetched from the browser cache.
          // @TODO: Should remote images cache?
          if ($cache->sendCachingHttpHeaders())
          {
            $response->setContentType($sf_pool_image->getMimeType()); 

            $response->addCacheControlHttpHeader('public');
            $response->addCacheControlHttpHeader('max_age', $cache->getLifetime());
          
            $response->setHttpHeader('Last-Modified', date('D, j M Y, H:i:s'));
            $response->setHttpHeader('Expires', date('D, j M Y, H:i:s', strtotime(sprintf('+ %u second', $cache->getLifetime()))));
          }
          
          $response->setHttpHeader('X-Is-Cached', 'no');
          
          // if thumbnail doesn't already exist
          // if it does - we get the file contents here or redirect
          if ($url = $cache->exists())
          {
            $this->redirect($url, 0, 301);
          }
          else
          {
            // create thumbnail
            $thumb = $resizer->save($cache->getDestination());
          
            // get thumbnail data and spit out
            $image_data = $thumb->toString();

            $cache->commit();
          }
          
          // If it's remote there'll be a redirect to the remote URL
          if (!$cache::IS_REMOTE)
          {
            if ($cache->sendCachingHttpHeaders()) $response->setHttpHeader('Content-Length', strlen($image_data));
            
            return $this->renderText($image_data);
          }
          else 
          {
            return sfView::NONE;
          }
        }
        // thumbnail could not be generated so let's spit out a placeholder instead 
        catch (sfImagePoolException $e)
        {
          if (sfConfig::get('app_sf_image_pool_placeholders', false))
          {
          	if (sfConfig::get('app_sf_image_pool_use_placeholdit', false)) $dest = sprintf('http://placehold.it/%ux%u&text=%s', $width, $height, urlencode(sfConfig::get('app_sf_image_pool_placeholdit_text', ' ')));
          	else if (sfConfig::get('app_sf_image_pool_use_placekitten', false)) $dest = sprintf('http://placekitten.com/g/%u/%u', $width, $height);
          	else $dest = sprintf('@image?width=%s&height=%s&filename=%s&method=%s', $width, $height, sfImagePoolImage::DEFAULT_FILENAME, $thumb_method);
            
            $this->logMessage($e->getMessage());
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