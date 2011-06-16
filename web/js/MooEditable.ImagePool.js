/*
---

name: MooEditable.ImagePool

  Adds sfImagePool functionality to MooEditable.

usage:
  Add MooEditable widget with all sfImagePool config options by using:

	// MooEditable textarea with ImagePool plugin
	$tag = '';
	sfImagePoolUtil::addImagePoolMooEditable($this, 'summary', $tag);

author:
  # - Jo Carter <jocarter@holler.co.uk>

requires:
  # - sfImagePoolPlugin
  # - sfMooToolsFormExtraPlugin

provides:
  # - MooEditable.Actions.imagepool
*/

/**
 * Slight hack to enable image-pool div to insert the URL of selected image into the editor
 */ 
var editorId;

MooEditable.Locale.define({
  imagePoolable: 'Add image from pool'
});


/**
 * Create new UI Dialog to handle image pool images
 */
MooEditable.UI.ImageDialog = function(editor) {
  // Image pool selector - create a new div - only if doesn't exist (i.e: multiple mooeditables on the page)
  if (!$('image-pool-editable')) {
    var newElement = new Element('div', { id: 'image-pool-editable' });
    $('body').grab(newElement, 'after');
  }
  
  // Select image - NOTE: must pass through URL in MooEditable config - see sfImagePoolUtil
  var  html = '<label class="dialog-label">Choose an image from the image pool *<br /><br />' +
            '<span class="selected-image"></span>' +
            '<button class="dialog-button image-pool-select" href="' + editor.options.chooserUrl + '">browse images</button><input type="hidden" class="image-url" />' +
         '</label>';
  
  // Add image options
  html += '<br class="clear" /><br /><label class="dialog-label">width * <input type="text" class="image-width" value="" style="width:50px !important;" />px</label>';
  html += '<label class="dialog-label">&nbsp;x height <input type="text" class="image-height" value="" style="width:50px !important;" />px</label>';
  html += '<br class="clear" /><br /><label class="dialog-label">crop image? <input type="checkbox" class="image-crop" value="yes" /> (by default will be scaled)</label>';
  html += '<br class="clear" /><br /><label class="dialog-label">alt text <input type="text" class="image-alt" value="" /></label>';
 
  
  // Add buttons
  html += '<br class="clear" /><br /><button class="dialog-button dialog-ok-button">' + MooEditable.Locale.get('ok') + '</button>'
  + '<button class="dialog-button dialog-cancel-button">' + MooEditable.Locale.get('cancel') + '</button>';
  
  return new MooEditable.UI.Dialog(html, {
    'class': 'mooeditable-image-dialog',
    
    // Catch button clicks
    onClick: function(e){
      if (e.target.tagName.toLowerCase() == 'button') e.preventDefault();
      
      var button = document.id(e.target);
      
      // If selected browse images
      if (button.hasClass('image-pool-select')){
    	// Set the editor id for the current editor
        editorId = editor.container.get('id');
      
        $('image-pool-editable').reveal();
        
        // AJAX request to get first page of images
        var request = new Request.HTML({
          'method':     'get',
          'url':        button.get('href'),
          'update':     $('image-pool-editable'),
          'onSuccess':  function () {
            ed_pagination();
            ed_images();
          },
          'onRequest':  function () {
            $('image-pool-editable').set('html','<img src="/sfImagePoolPlugin/images/indicator.gif" />');
          }
        }).send('multiple='+false);
      }
      // Cancel - clear and close
      else if (button.hasClass('dialog-cancel-button')){
    	// Clear entered options and reset
    	clear_inputs(this.el);
    	
        this.close();
      } 
      // OK - validate, close, insert image, and clear
      else if (button.hasClass('dialog-ok-button')){
        var imageWidth = this.el.getElement('.image-width').value;
        var imageUrl = this.el.getElement('.image-url').value;
        
        // Requires image url and width - other parameters are optional
        if ('' == imageWidth || '' == imageUrl) {
          alert('Please select an image AND enter at least the required width');
          this.el.getElement('.image-width').focus();
        }
        else {
          this.close();
          
          var imageHeight = this.el.getElement('.image-height').value;
          // As per image pool helper - if no height, use width
          if ('' == imageHeight) imageHeight = imageWidth;
          var imageMethod = (this.el.getElement('.image-crop').checked ? 'crop' : 'scale');
          var imageAlt = this.el.getElement('.image-alt').value;

          // Construct image URL dynamically
          var imageUrl = '/'+editor.options.imageFolder+'/'+imageMethod+'/'+imageWidth+'/'+imageHeight+'/'+imageUrl;
          
          // Insert image into editor
          var div = new Element('div');
          var imgElement = new Element('img');
          imgElement.set('src', imageUrl);
          imgElement.set('class', editor.options.imageClass);
          imgElement.set('alt', imageAlt);
          div.grab(imgElement);
          
          editor.selection.insertContent(div.get('html'));
          
          // Clear entered options and reset
          clear_inputs(this.el);
        }
      }
    }
  });
};


/**
 * Add extra option to toolbar to insert image from sfImagePool
 */
Object.append(MooEditable.Actions, {

  imagepool: {
    title: MooEditable.Locale.get('imagePoolable'),
    dialogs: {
      prompt: function(editor){
		// Opens the above defined UI Dialog
        return MooEditable.UI.ImageDialog(editor);
      }
    },
    command: function(){
        this.dialogs.imagepool.prompt.open();
    }
  }
});


/**
 * Clear and reset the image pool dialog
 * @param Element e the editor
 */
var clear_inputs = function (e) {
  e.getElement('.image-width').set('value','');
  e.getElement('.image-url').set('value','');
  e.getElement('.image-height').set('value','');
  e.getElement('.image-crop').set('checked',false);
  e.getElement('.selected-image').empty();
  e.getElement('.image-pool-select').set('html', 'browse images');
}


/**
 * Add click events to the images in the pool to allow them to set the hidden
 * input field and show the selected image
 */
var ed_images = function () {
  $(document.body).getElements("#image-pool-editable img").each(
    function(el) {
      el.addEvent('click', function () {
        selectedFilename  = this.get('title');
        selectedAlt  = this.get('alt');

        // Hide the div
        $('image-pool-editable').dissolve();
        
        // Set the hidden input in the current editor
        $(editorId).getElement('.image-url').set('value', selectedFilename);
        $(editorId).getElement('.image-alt').set('value', selectedAlt);
        
        // Show the selected image
        $(editorId).getElement('.image-pool-select').set('html', 'change image');
        $(editorId).getElement('.selected-image').empty();
        $(editorId).getElement('.selected-image').adopt(this);
      });
   });
};


/**
 * Add click events to turn pagination into AJAX
 */
var ed_pagination = function () {
  $(document.body).getElements('#image-pool-editable #pagination a').each(
    function (el) {
      el.addEvent('click', function (ev) {
        ev.preventDefault();
      
        request = new Request.HTML({
          'method':     'get',
          'url':        this.get('href'),
          'update':     $('image-pool-editable'),
          'onSuccess':  function () {
            ed_pagination();
            ed_images();
          },
          'onRequest':  function () {
            $('image-pool-editable').set('html','<img src="/sfImagePoolPlugin/images/indicator.gif" />');
          }
        }).send('multiple='+false);
      });
    }
  );
};
