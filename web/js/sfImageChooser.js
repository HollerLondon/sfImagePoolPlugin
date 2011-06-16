var toggleId        = 'toggleThumbnails';
var containerId     = 'thumbnailsContainer';
var paginationId    = 'pagination';
var chooserId       = 'imageChooser';

var hidden = true;

var images = function ()
{
  $(document.body).getElements("#thumbnailsContainer img").each(
    function(el)
    {
      el.addEvent('click',
        function ()
        {
          selectedFilename  = this.get('title');
          selectedId        = this.get('rel');
          
          if($('selectedImage').getElement('input[value='+selectedId+']'))
          {
            return;
          }
          
          im = new Element('img',{
            'src':    this.get('src'),
            'title':  'Selected Image',
            'events': {
              'click':    removeItem
            }
          });
          
          if(!$(chooserId).hasClass('multiple'))
          {
            $('selectedImage').empty();

            hidden = true;
            $('thumbnailsContainer').dissolve();
            $('toggleThumbnails').set('html', 'Show Images');
          
            h4 = new Element('h4',{html: 'Selected Image:'});
            $('selectedImage').adopt(im);
            
            input = new Element('input',{
              'type':         'hidden',
              'value':        selectedId,
              'name':         $('sf-image-id').get('name')
            });
            $('selectedImage').adopt(input);

            $('selectedImage').reveal();
          }
          else
          {
            $('selectedImage').adopt(im);
            input = new Element('input',{
              'type':         'hidden',
              'value':        selectedId,
              'name':         $('sf-image-id').get('name')
            });
            $('selectedImage').adopt(input);
          }
        }
      );
    }
  );
};

var removeItem = function ()
{
  input = this.getNext('input');
  this.dispose();
  input.dispose();
};

var pagination = function ()
{
  $(document.body).getElements('#pagination a').each(
    function (el)
    {
      el.addEvent('click',
        function (ev)
        {
          ev.preventDefault();
          
          multiple = $(chooserId).hasClass('multiple');
          
          request = new Request.HTML({
            'method':     'get',
            'url':        this.get('href'),
            'update':     $('thumbnailsContainer'),
            'onSuccess':  function ()
            {
              pagination();
              images();
            },
            'onRequest':  function ()
            {
              $('pagination').set('html','<img src="/sfImagePoolPlugin/images/indicator.gif" />');
            }
          }).send('multiple='+multiple);
        }
      );
    }
  );
};

window.addEvent('domready',
  function ()
  {
    pagination();
    images();

    $('toggleThumbnails').addEvent('click',
      function (ev)
      {
        ev.preventDefault();
        if(hidden)
        {
          $('toggleThumbnails').set('html','Hide Images');
          $('thumbnailsContainer').reveal();
        }
        else
        {
          $('toggleThumbnails').set('html','Show Images');
          $('thumbnailsContainer').dissolve();
        }
        hidden = !hidden;
      }
    );
    
    $('selectedImage').getElements('img').each(
      function (img)
      {
        img.addEvent('click', removeItem );
      }
    );
    
  }
);
