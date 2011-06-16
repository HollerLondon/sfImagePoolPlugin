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
     * 
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

        $vars = array(
            'pager'     => Doctrine_Core::getTable('sfImagePoolImage')->getPager($per_page, 1, $object),
            'name'      => $name,
            'object'    => $object,
            'per_page'  => $per_page,
            'images'    => $object->getPoolImages(),
            'multiple'  => (bool) $object->allowSelectMultiple(),
        );

        return get_partial('sfImagePoolAdmin/widget', $vars);
    }
    
    /**
     * JS required by widget.
     * 
     * Requires MooTools to be in the global /js folder.
     * 
     * Requires MooTools.More:
     *  * More/Fx.Reveal
     */
    public function getJavaScripts()
    {
        //$prefix = ($this->getOption('ssl') ? 'https://' : 'http://');
      
        return array(
            //$prefix.'ajax.googleapis.com/ajax/libs/mootools/1.3.1/mootools-yui-compressed.js',
            //'/sfImagePoolPlugin/js/mootools-more.js',
            '/sfImagePoolPlugin/js/sfImageChooser.js',
        );
    }
    
    /**
     * Stylesheets required by widget
     *
     * @return unknown
     */
    public function getStylesheets()
    {
        return array('/sfImagePoolPlugin/css/image-chooser.css' => 'all');
    }
}
?>