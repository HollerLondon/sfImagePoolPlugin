// Use for image choosers defined in mooeditable and here
var sfImageChooser = {};

var ImageChooser = new Class(
{
  Implements:   [ Options ],
  
  options: 
  {
    toggleClass:              'toggleThumbnails',
    containerClass:           'thumbnailsContainer',
    paginationClass:          'pagination',
    selectedImageClass:       'selectedImage',
    selectedImageInputClass:  'sf-image-id',
    uploadImageClass:         'upload_new_image',
    uploadBackClass:          'image_upload_back',
    imageOnClickEvent:        null, // for custom behaviour on image click
    onSuccessEvent:           null  // any additional events that should be called on success (function)
  },
  
  imageChooser:         null,
  toggle:               null,
  selectedImage:        null,
  selectedImageInput:   null,
  thumbnailsContainer:  null,
  uploadImage:          null,
  pagination:           null,
  multiple:             false,
  hidden:               true,
  loadingImage:         '<img src="/sfImagePoolPlugin/images/indicator.gif" alt="Loading" />',
  
  initialize: function (imageChooserDiv, options) 
  {
    this.setOptions(options);
    
    this.imageChooser         = imageChooserDiv;
    this.multiple             = this.imageChooser.hasClass('multiple');
    
    this.selectedImage        = this.imageChooser.getElement('.'+this.options.selectedImageClass);
    this.thumbnailsContainer  = this.imageChooser.getElement('.'+this.options.containerClass);
    this.toggle               = this.imageChooser.getElement('.'+this.options.toggleClass);
    
    this.setUpUploadImage();
    this.setUpPagination();
    this.setUpImages();
    
    if (this.toggle) 
    {
      this.setUpToggle();
    }
    
    if (this.options.onSuccessEvent) 
    {
      this.options.onSuccessEvent();
    }
  },
  
  setUpToggle: function ()
  {
    this.toggle.addEvent('click',
      function (e)
      {
        var self = this;
        var el   = e.target;
      
        e.preventDefault();
        
        if (self.hidden)
        {
          self.toggle.set('html', 'Hide Images');
          self.thumbnailsContainer.reveal();
        }
        else
        {
          self.toggle.set('html', 'Show Images');
          self.thumbnailsContainer.dissolve();
        }
        
        self.hidden = !self.hidden;
      }.bind(this)
    );
  },
  
  setUpImages: function ()
  {
    if (this.selectedImage)
    {
      this.selectedImage.getElements('img').each(
        function (img)
        {
          img.addEvent('click', this.removeItem );
        }.bind(this)
      );
    }
    
    this.thumbnailsContainer.getElements('img').each(
      function (el) 
      {
        // Use custom event if set - because may have different functionality for MooEditable version
        if (this.options.imageOnClickEvent)
        {
          el.addEvent('click', this.options.imageOnClickEvent.bind(this));
        }
        // Default event
        else
        {
          el.addEvent('click', this.imageOnClick.bind(this));
        }
      }.bind(this)
    );
  },
  
  imageOnClick: function (e)
  {
    var selectedId, im, input;
    var self    = this;
    var el      = e.target;
    
    if (!self.multiple)
    {
      self.hidden = true;
      self.thumbnailsContainer.dissolve();
      self.imageChooser.getElement('.'+self.options.toggleClass).set('html', 'Show Images');
    }
    
    // Add item
    return self.addItem(el);
  },
  
  addItem: function (el)
  {
    var selectedId, im, input;
    var self    = this;
    
    selectedId = el.get('rel');
    
    if (self.selectedImage.getElement('input[value='+selectedId+']'))
    {
      return;
    }
    
    im = new Element('img',
    {
      'src':    el.get('src'),
      'title':  el.get('title'),
      'alt':    el.get('alt'),
      'events': 
      {
        'click':    self.removeItem
      }
    });
    
    input = new Element('input',
    {
      'type':         'hidden',
      'value':        selectedId,
      'name':         self.imageChooser.get('data-chooser-name') + '[]'
    });
    
    if (!self.multiple)
    {
      self.selectedImage.empty();
      
      self.selectedImage.adopt(im);
      self.selectedImage.adopt(input);
    }
    else
    {
      self.selectedImage.adopt(im);
      self.selectedImage.adopt(input);
    }
  },
  
  removeItem: function (e)
  {
    input = this.getNext('input');
    this.dispose();
    input.dispose();
  },
  
  setUpPagination: function ()
  {
    this.pagination = this.thumbnailsContainer.getElement('.'+this.options.paginationClass);
    
    if (this.pagination)
    {
      this.pagination.getElements('a').each(
        function (el) 
        {
          el.addEvent('click', function (e)
          {
            var request;
            var self = this;
            var el   = e.target;
            
            e.preventDefault();
            
            request = new Request.HTML({
              'method':     'get',
              'url':        el.get('href'),
              'update':     self.thumbnailsContainer,
              'onSuccess':  function ()
              {
                this.setUpUploadImage();
                this.setUpPagination();
                this.setUpImages();
                
                if (this.options.onSuccessEvent)
                {
                  this.options.onSuccessEvent();
                }
                
              }.bind(self),
              
              'onRequest':  function ()
              {
                this.pagination.set('html', self.loadingImage);
                
              }.bind(self)
              
            }).send('multiple='+self.multiple);
            
          }.bind(this));
        }.bind(this)
      );
    }
  },
  
  setUpUploadImage: function ()
  {
    this.uploadImage = this.thumbnailsContainer.getElement('.'+this.options.uploadImageClass);
    
    this.uploadImage.addEvent('click', 
      function(e) 
      {
        var iFrame, paginationLink, paginationDiv;
        var self = this;
        var el   = e.target;
        
        e.stop();
        
        self.pagination.set('html', self.loadingImage);
        
        // Create iFrame and load page in the iFrame
        iFrame = new Element('iframe', { 'src': el.get('href'), 'width':'100%', 'height':'250px', 'id': self.imageChooser.id + '_iframe' });
        paginationLink = new Element('a', { 'href' : $(self.imageChooser.id + '_page_1').get('value'), 'html' : '&laquo; Back to selection', 'class' : self.options.uploadBackClass });
        paginationDiv = new Element('p', { 'class': self.options.paginationClass });
        paginationDiv.adopt(paginationLink);
        
        self.thumbnailsContainer.empty();
        self.thumbnailsContainer.adopt(iFrame);
        self.thumbnailsContainer.adopt(paginationDiv);
        
        self.setUpPagination();
      }.bind(this)
    );
  },
  
  closeIframe: function ()
  {
    var el = this.pagination.getElement('.'+this.options.uploadBackClass);
    var e  = new Event.Mock(el, 'click');
    
    // Need to send event otherwise can't use this / e.target
    el.fireEvent('click', e);
  },
  
  resizeIframe: function ()
  {
    var iframe = new IFrame($(this.imageChooser.id + '_iframe'));
    
    try
    {
      var height = 0;
      
      if (iframe.contentDocument)
      {
        height = iframe.contentDocument.height;
      }
     
      if (iframe.contentWindow && (!height || 0 == height))
      {
        height = iframe.contentWindow.document.body.scrollHeight;
      }
      
      if (height && 0 < height)
      {
        height += 10;
        $(this.imageChooser.id + '_iframe').set("height", height + 'px');
      }
    }
    catch (e) {}
  }
});

/**
 * Creates a Mock event to be used with fire event
 * 
 * @param Element target an element to set as the target of the event - not required
 * @param string type the type of the event to be fired. Will not be used by IE - not required.
 *
 * From : http://mootools.net/forge/p/event_mock
 */
Event.Mock = function (target, type)
{
  var e = window.event;
  type = type || 'click';
  
  if (document.createEvent)
  {
    e = document.createEvent('HTMLEvents');
    
    e.initEvent(
      type,  // event type
      false, // bubbles - set to false because the event should like normal fireEvent
      true   // cancelable
    );
  }
  
  var ev = new Event(e);
  ev.target = target;
  
  return ev;
};

var bound = false;
var sfImageChooser = {};

window.addEvent('domready', function ()
{
  // fixes double binding
  if (bound) 
  {
    return;
  }
  bound = true;
  
  // potentially multiple choosers
  $$('.imageChooser').each(function (el) 
  {
    var id = el.id;
    sfImageChooser[id] = new ImageChooser(el);
  });
  
  // iframe cancel button
  if ($('cancel_upload'))
  {
    var id = $('cancel_upload').get('rel');
    
    parent.sfImageChooser[id].resizeIframe(); // resize on load of content
    
    $('cancel_upload').addEvent('click', function(e) 
    {
      parent.sfImageChooser[id].closeIframe();
    });
  }
});
