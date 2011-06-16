<?php
/**
 * Class for editing an object's associated tags
 */
class sfDoctrineTaggableInput extends sfWidgetFormInputText
{
    /**
     * 
     */
    public function configure($options = array(), $attributes = array())
    {
        parent::configure($options, $attributes);

        $this->setAttribute('size', 60);
        $this->setAttribute('maxlength', 255);
        $this->setAttribute('class', 'tag-input');
        $this->setAttribute('autocomplete', 'off');
    }

    public function render($name, $value = null, $attributes = array(), $errors = array())
    {
        if(is_array($value))
        {
            $value = implode(", ", $value);
        }
        
        return $this->renderTag('input', array_merge(array('type' => $this->getOption('type'), 'name' => $name, 'value' => $value), $attributes));
    }    
}
?>