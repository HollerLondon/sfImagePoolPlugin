<?php
/**
 * Image file validator.
 * 
 * Primary purpose is to check that an uploaded image doesn't exceed given dimensions,
 * and if it does, it will resize it. This may or may not be the best place for
 * the logic, not sure.
 * 
 * The thing is, it will create a smaller filesize if the file is reduced. The
 * the maximum filesize is checked after a potential resize, due to the late call
 * of parent::doClean(). That means that the given maximum filesize refers to the
 * post-resized value, effectively giving you a maximum size for resized files.
 * 
 * @uses   ImageTransform
 * @author John Grimsey <john@johngrimsey.co.uk>
 */
class sfValidatorImageFile extends sfValidatorFile
{
    /**
     * Adds the following options to sfValidatorFile:
     * 
     * - max_width (default 3000px)
     * - max_height (default 3000px)
     * 
     * and the following error codes:
     * 
     * - max_width
     * - max_height
     *
     * @param unknown_type $options
     * @param unknown_type $messages
     */
    protected function configure($options = array(), $messages = array())
    {
        $this->addOption('required_width', false);
        $this->addOption('required_height', false);
        
        $this->addOption('max_width', 3000);
        $this->addOption('max_height', 3000);
        $this->addOption('resize', true);
        
        $this->addMessage('max_width', 'Image width (%width%px) is too big (maximum allowed is %max_width%px).');
        $this->addMessage('max_height', 'Image height (%height%px) is too big (maximum allowed is %max_height%px).');
        $this->addMessage('max_dimensions', 'Image dimensions (%width%px x %height%px) are too big (maximum allowed is %max_width%px &times; %max_height%px).');
        $this->addMessage('required_dimensions', 'Image dimensions must match %required_width%px &times; %required_height%px (it was %width%px &times; %height%px)');
        
        parent::configure($options, $messages);
    }

    /**
     * Check dimensions, perform resize and then call parent validator.
     * 
     * @param array $value
     * @return sfValidatedFile
     */
    protected function doClean($value)
    {
        $file = parent::doClean($value);
        
        // resize the image by default
        if($this->getOption('resize'))
        {
            // first of all, resize to given maximum and/or width but check for exception
            // if unsupported file format
            // FIXME this isn't elegant and should fail better (mime_types check should fail first before resize attempted)
            try
            {
                $image = new ImageTransform($file->getTempName(), $file->getType());
                $image->fit($this->getOption('max_width'), $this->getOption('max_height'));
                $image->save($file->getTempName());
            }
            catch(Exception $e)
            {
                // do nothing
            }
        }
        
        else
        {
            // fetch image info
            list($width, $height, $type, $attr) = getimagesize($file->getTempName()); 
            
            // do we have some exact required dimensions?
            if($this->getOption('required_width') !== false || $this->getOption('required_height') !== false)
            {
                $passes_width = ($required_width = $this->getOption('required_width'))
                    ? $width == $required_width
                    : true;
                    
                $passes_height = ($required_height = $this->getOption('required_height'))
                    ? $height == $required_height
                    : true;
                
                if(!$passes_height || !$passes_width)
                {
                    throw new sfValidatorError($this, 'required_dimensions', array(
                        'width'           => $width,
                        'height'          => $height,
                        'required_width'  => $required_width,
                        'required_height' => $required_height,
                    ));
                }
            }
            
            // check both dimensions to show useful error about both width and height if needed
            if(($width > $this->getOption('max_width')) && ($height > $this->getOption('max_height')))
            {
                throw new sfValidatorError($this, 'max_dimensions', array(
                    'width'      => $width,
                    'height'     => $height,
                    'max_width'  => $this->getOption('max_width'),
                    'max_height' => $this->getOption('max_height'),
                ));
            }
            
            // check width
            elseif($width > $this->getOption('max_width'))
            {
                throw new sfValidatorError($this, 'max_width', array('width' => $width, 'max_width' => $this->getOption('max_width')));
            }
            
            // check height        
            elseif($height > $this->getOption('max_height'))
            {
                throw new sfValidatorError($this, 'max_height', array('height' => $height, 'max_height' => $this->getOption('max_height')));
            }
        }
        
        // now tell sfValidatorFile to do its own checks 
        return $file;
    }
}
?>