<?php
/**
 * Extending the widget to handle JS and CSS dependancies
 * 
 * @author Jo Carter <jocarter@holler.co.uk>
 */
class sfWidgetFormTextareaMooEditableImagePool extends sfWidgetFormTextareaMooEditable
{
  /**
   * Constructor.
   *
   * Available options: (defaults set in plugin app.yml)
   *
   *  see parent - NOTE if anything sent through for extra toolbar / config it will cause problems
   *  plus:
   *  * image_tag - tag to filter images available
   *  * image_width / image_height - default dimensions to prefill for the textarea
   *
   * @param array $options     An array of options
   * @param array $attributes  An array of default HTML attributes
   *
   * @see sfWidgetForm
   */
  protected function configure($options = array(), $attributes = array())
  {
    // Add options to configure image pool
    $this->addOption('image_tag', '');
    $this->addOption('image_width', null);
    $this->addOption('image_height', null);
    
    if (!function_exists('url_for')) 
    {
      sfApplicationConfiguration::getActive()->loadHelpers(array('Url'));
    }
    
    // Set defaults
    if (!isset($options['image_tag']))    $options['image_tag']     = '';
    if (!isset($options['image_width']))  $options['image_width']   = '';
    if (!isset($options['image_height'])) $options['image_height']  = '';
    if (!isset($options['extratoolbar'])) $options['extratoolbar']  = sfConfig::get('app_mooeditable_default_extra_toolbar');
    if (!isset($options['config']))       $options['config']        = sfConfig::get('app_mooeditable_default_config');
    
    // Set up image pool toolbar
    $options['extratoolbar'] = $options['extratoolbar'] . ' imagepool';
    
    $options['config']       = sprintf(
          "%s
          chooserUrl:  '%s', imageFolder: '%s', imageClass: 'image-pool', defaultWidth: '%s', defaultHeight: '%s'",
          $options['config'],
          url_for('sf_image_pool_chooser', array('tag'=>$options['image_tag'])), sfConfig::get('app_sf_image_pool_folder'),
          $options['image_width'],
          $options['image_height']
        );
      
    parent::configure($options, $attributes);
    
    // Set the configured options
    $this->setOption('config',       $options['config']);
    $this->setOption('extratoolbar', $options['extratoolbar']);
  }
  
  public function getJavaScripts()
  {
    $js = parent::getJavascripts();
    
    return array_merge($js, 
                        array('/sfImagePoolPlugin/js/sfImageChooser.js',
                              '/sfImagePoolPlugin/js/MooEditable.ImagePool.js'));
  }

  public function getStylesheets()
  {
    $css = parent::getStylesheets();
  
    return array_merge($css, array('/sfImagePoolPlugin/css/MooEditable.ImagePool.css'=>'all'));
  }
}