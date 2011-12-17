<?php
/**
 * Fancy MooTools-powered image chooser widget for selecting an image to use
 * with a model from those stored in the DB. 
 * 
 * @author John Grimsey
 * @author Ben Lancaster
 */
class sfWidgetFormImagePoolChooser extends sfWidgetForm
{
  /**
   * Set up widget
   */
  public function configure($options = array(), $attributes = array())
  {
    $this->addOption('object');
    $this->addOption('ssl', false);
    $this->setLabel('Images');
  }
  
  /**
   * Render the image browser widget
   *
   * @param string $name
   * @param mixed $value
   * @param array $attributes
   * @param array $errors
   * @return string Widget HTML
   */
  public function render($name, $value = null, $attributes = array(), $errors = array())
  {
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Partial'));
    
    $object   = $this->getOption('object');
    $per_page = sfConfig::get('app_sf_image_pool_chooser_per_page', 24);
    
    $tag = $object->getTagRestriction();
    if ($tag) $tag = implode(',', $tag); // Because we use this for image upload

    $vars = array(
        'pager'     => sfImagePoolImageTable::getInstance()->getPager($per_page, 1, $object),
        'name'      => $name,
        'object'    => $object,
        'per_page'  => $per_page,
        'images'    => $object->getPoolImages(),
        'multiple'  => (bool) $object->allowSelectMultiple(),
        'tag'       => $tag
    );

    return get_partial('sfImagePoolAdmin/widget', $vars);
  }
  
  /**
   * JS required by widget.
   * 
   * @requires MooTools core
   * @requires MooTools.More:
   *  * More/Fx.Reveal
   *  
   * @return array Array of javascripts
   */
  public function getJavaScripts()
  {
    return array(
        '/sfImagePoolPlugin/js/sfImageChooser.js',
    );
  }
  
  /**
   * Stylesheets required by widget
   *
   * @return array Array of stylesheets
   */
  public function getStylesheets()
  {
    return array('/sfImagePoolPlugin/css/image-chooser.css' => 'all');
  }
}
