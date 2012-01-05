/*
---

name: MooEditable.ImagePool

  Adds sfImagePool functionality to MooEditable.

usage:
  see plugin README

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

MooEditable.Locale.define(
{
  imagePoolable: 'Add image from pool'
});


/**
 * Create new UI Dialog to handle image pool images
 */
MooEditable.UI.ImagePoolDialog = function(editor) 
{
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
  
  return new MooEditable.UI.Dialog(html, 
  {
    'class': 'mooeditable-imagepool-dialog',
    
    // Catch button clicks
    onClick: function (e)
    {
      if (e.target.tagName.toLowerCase() === 'button')
      {
        e.preventDefault();
      }
      
      var button = document.id(e.target);
      
      // If selected browse images
      if (button.hasClass('image-pool-select'))
      {
        // Set the editor id for the current editor
        editorId = editor.container.get('id');
      
        // Image pool selector - create a new div - only if doesn't exist (i.e: multiple mooeditables on the page)
        // Always reuses the div - but will target correct mooeditable (div destroyed on cancel/close)
        if (!$('image-pool-editable')) 
        {
          var imageChooserDiv = new Element('div', { id: 'image-pool-editable' });
          var thumbnailsContainerDiv = new Element('div', { 'class': 'thumbnailsContainer' }); // to match other iamge chooser
          imageChooserDiv.adopt(thumbnailsContainerDiv);
          
          $(editorId).getElement('iframe').grab(imageChooserDiv, 'after');
        }
        
        $('image-pool-editable').reveal();
        
        // Set width and height from defaults if they exist
        $(editorId).getElement('.image-width').set('value',  editor.options.defaultWidth);
        $(editorId).getElement('.image-height').set('value', editor.options.defaultHeight);
        
        var thumbnailsContainer = $('image-pool-editable').getElement('.thumbnailsContainer');
        
        // AJAX request to get first page of images
        var request = new Request.HTML(
        {
          'method':     'get',
          'url':        button.get('href') + '&chooser_id=image-pool-editable', // so the iframe knows what to close
          'update':     thumbnailsContainer,
          'onSuccess':  function () 
          {
            sfImageChooser['image-pool-editable'] = new ImageChooser($('image-pool-editable'), 
              {
                onSuccessEvent: function () 
                { 
                  MooEditable.Actions.imagepool.createClose(); 
                },
                imageOnClickEvent: function (e) 
                  { 
                    var self = this;
                    var el   = e.target;
                  
                    var selectedFilename  = el.get('title');
                    var selectedAlt       = el.get('alt');
            
                    $('image-pool-editable').dissolve();
                    
                    // Set the hidden input in the current editor
                    $(editorId).getElement('.image-url').set('value', selectedFilename);
                    $(editorId).getElement('.image-alt').set('value', selectedAlt);
                    
                    // Show the selected image
                    $(editorId).getElement('.image-pool-select').set('html', 'change image');
                    $(editorId).getElement('.selected-image').empty();
                    $(editorId).getElement('.selected-image').adopt(el);
                  }
              }
            );
          },
          'onRequest':  function () 
          {
            this.set('html', '<img src="/sfImagePoolPlugin/images/indicator.gif" alt="Loading" />');
            
          }.bind(thumbnailsContainer)
          
        }).send('multiple='+false);
      }
      // Cancel - clear and close
      else if (button.hasClass('dialog-cancel-button'))
      {
        this.close();
        
        // Clear entered options and reset
        MooEditable.Actions.imagepool.clear(this.el);
        $('image-pool-editable').destroy();
      } 
      // OK - validate, close, insert image, and clear
      else if (button.hasClass('dialog-ok-button'))
      {
        var imageWidth = this.el.getElement('.image-width').value;
        var imageUrl   = this.el.getElement('.image-url').value;
        
        // Requires image url and width - other parameters are optional
        if ('' == imageWidth || '' == imageUrl) 
        {
          alert('Please select an image AND enter at least the required width');
          this.el.getElement('.image-width').focus();
        }
        else 
        {
          this.close();
          
          var imageHeight = this.el.getElement('.image-height').value;
          
          // As per image pool helper - if no height, use width
          if ('' == imageHeight) 
          {
            imageHeight = imageWidth;
          }
          var imageMethod = (this.el.getElement('.image-crop').checked ? 'crop' : 'scale');
          var imageAlt = this.el.getElement('.image-alt').value;

          // Construct image URL dynamically
          imageUrl = '/'+editor.options.imageFolder+'/'+imageMethod+'/'+imageWidth+'/'+imageHeight+'/'+imageUrl;
          
          // Insert image into editor
          var div = new Element('div');
          var imgElement = new Element('img');
          imgElement.set('src', imageUrl);
          imgElement.set('class', editor.options.imageClass);
          imgElement.set('alt', imageAlt);
          div.grab(imgElement);
          
          editor.selection.insertContent(div.get('html'));
          
          // Clear entered options and reset
          MooEditable.Actions.imagepool.clear(this.el);
          $('image-pool-editable').destroy();
        }
      }
    }
  });
};


/**
 * Add extra option to toolbar to insert image from sfImagePool
 */
MooEditable.Actions.imagepool = 
{
  title: MooEditable.Locale.get('imagePoolable'),
  dialogs: 
  {
    prompt: function (editor)
    {
      return MooEditable.UI.ImagePoolDialog(editor);
    }
  },
  command: function ()
  {
    this.dialogs.imagepool.prompt.open();
  },
  /**
   * Clear and reset the image pool dialog
   * @param Element e the editor
   */
  clear: function (e)
  {
    e.getElement('.image-width').set('value','');
    e.getElement('.image-url').set('value','');
    e.getElement('.image-alt').set('value','');
    e.getElement('.image-height').set('value','');
    e.getElement('.image-crop').set('checked',false);
    e.getElement('.selected-image').empty();
    e.getElement('.image-pool-select').set('html', 'browse images');
  },
  /**
   * Create close button that hides the editor div
   */
  createClose: function ()
  {
    var closeElement = new Element('span', { 'id' : 'close_image_pool', 'html' : 'close x' });
    closeElement.inject($('image-pool-editable'), 'top');
    
    $('close_image_pool').addEvent('click', function (e) { $('image-pool-editable').dissolve(); });
  }
};
