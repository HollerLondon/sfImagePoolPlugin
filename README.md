sfImagePoolPlugin
=================

Introduction
------------

A plugin that provides a global pool of images. Images can be associated with one or more Doctrine_Record objects. Images can then be resized/cropped on the
fly and are cached on the filesystem (or on the edge, depending on preference). Images in the pool can be tagged, and a model's use of images restricted to a
specific tag. Custom crops may be uploaded, which are then used instead of an automatically generated crop for those dimensions.

The plugin is comprised of:

 * `sfImagePoolable` Doctrine template (aka behaviour)
 * Admin generator module `sfImagePoolAdmin` for adding and removing the to the image pool, editing images (tags, name, caption, description) and uploading custom crops.
 * `sfImagePool` module for image transformations and output
 * `sfImagePoolHelper` for easy inclusion of a model's image(s) in templates
 * `sfWidgetFormImagePoolChooser` widget for selecting images and a featured image (when multiple images are being selected)
 * Plugin for `sfMooToolsFormExtraPlugin` to enable image pool images to be added into a rich text area
 * An improved sfThumbnail Adapter for ImageMagick which will optionally sharpen images as they're sized


Dependencies
------------

 * Doctrine 1.x
 * Symfony 1.3/1.4
 * [sfThumbnailPlugin](http://www.symfony-project.org/plugins/sfThumbnailPlugin)
   * A image manipulation library depending on which sfThumbnail adapter you choose. Defaults to `ImagePoolImageMagickAdapter`
 * MooTools Core 1.3.x
 * MooTools More 1.3.x:
  * Fx.Reveal
  
  
Optional Dependencies
---------------------

 * [sfDoctrineActAsTaggablePlugin](http://www.symfony-project.org/plugins/sfDoctrineActAsTaggablePlugin) (but only if tagging: true) in the options section of the model definition for sfImagePoolImage in `schema.yml`
 * [sfMooToolsFormExtraPlugin](https://github.com/HollerLondon/sfMooToolsFormExtraPlugin) - sfImagePoolPlugin includes an `sfImaegPoolPlugin` Image Chooser for [MooEditable](http://cheeaun.github.com/mooeditable/), and `sfMooToolsFormExtraPlugin` provides a MooEditable Symfony form widget - see _Optional extensions_.
 * [Rackspace Cloud files](https://github.com/rackspace/php-cloudfiles.git) - if storing image files on a Rackspace cloud this library is required in `lib/vendor/rackspace`
  
### Rackspace Cloud files: Note for SVN

If using SVN you will need to add these dependancies as `svn:externals` in the project's `lib/vendor` folder

    rackspace            https://svn.github.com/rackspace/php-cloudfiles.git

You will then need to autoload these files in the application's `config/autoload.yml`

	autoload:
	  vendor_rackspace:
	    path:      %SF_LIB_DIR%/vendor/rackspace
        recursive: on


### Rackspace Cloud files: Note for Git

The plugin's `lib/vendor` folder contains submodules for the Rackspace Cloud files API library, if exporting this repository then these files will also need to be exported

    [submodule "lib/vendor/rackspace"]
      path = lib/vendor/rackspace
      url = https://github.com/rackspace/php-cloudfiles.git
  

Setup
-----

### 1. Enable the plugin

In `ProjectConfiguration`:
  
    class ProjectConfiguration extends sfProjectConfiguration
    {
      public function setup()
      {
        $this->enablePlugin('sfDoctrinePlugin', 'sfImagePoolPlugin');
      }
    }
    ...
    
  
### 2. Enable modules

Typically, `sfImagePoolAdmin` in the backend app, and `sfImagePool` in all apps where you need to output images.

Every application that will handle images from the image pool should have the `sfImagePool` module enabled in the app's `settings.yml` (or globally in the project's `settings.yml`):

    all:
     .settings:
      enabled_modules: [ sfImagePool ]
    
Additionally, any application that needs the image chooser widget (e.g. the Content Management System) will need to have the `sfImagePoolAdmin` module enabled:

    all:
     .settings:
      enabled_modules: [ sfImagePool, sfImagePoolAdmin ]
     
It's probably a good idea to enable the `ImagePoolHelper` for all apps by default too:

    all:
     .settings:
      standard_helpers: [ ImagePool ]
      

### 3. Add behaviour to model(s) in schema.yml

#### Enable tagging (optional)

Decide whether tagging should be enabled (requires `sfDoctrineActAsTaggablePlugin`) - add in `doctrine/schema.yml`

    sfImagePoolImage:
      options:
        tagging:  true

#### Add behaviour

    MyModel:
      actAs: [sfImagePoolable]
      ...
    
#### Options

You can specify whether the model should be allowed to have multiple images, or just a single image (default) by using the multiple option. The first image selected for the model
will become the 'featured' image.

    MyModel:
      actAs:
        sfImagePoolable: { multiple: true }    
        ...
    
To restrict a model's use of images to those with the tag `foo`, use the tag option.

    MyModel:
      actAs:
        sfImagePoolable: { tag: foo }    
        ...
    
To have associated images and files deleted when your object is deleted, set shared_images option to false (default is true). When true and your object is deleted, 
only the lookup entries are deleted, images objects and files are left untouched (i.e. remain "in the pool").

    MyModel:
      actAs:
        sfImagePoolable: { shared_images: false }    
        ...
 

### 4. Add an image chooser widget to MyModelForm

To associate images with a model, the sfWidgetFormImagePoolChooser widget must be added to the model's form. The validator is required so that Symfony doesn't 
reject the values submitted by the additional widget.

    class MyModelForm extends BaseMyModelForm
    {
      public function configure()
      {
        sfImagePoolUtil::addImageChooser($this);
      }
    }

*Note:* the form's object is passed to the widget so that it may fetch currently associated images and any `sfImagePoolable` options specific to that model.


### 5. Customise plugin options

The following options may be overridden in your `app.yml` files:

    all:
      sf_image_pool:
        cache_lifetime:     7776000 # three months
        chooser_per_page:   24
    
        mimes:              [image/jpeg, image/jpg, image/png, image/pjpeg, 'image/x-png']

        # Maximum upload size to accept
        maxfilesize:        5242880
    
        # Folder within the web/ folder to store crops
        folder:             image-pool

        placeholders:       false # If true, use file placeholder.jpg if an image can't be found
        use_placeholdit:    false # if true, returns handy placeholder images from http://placehold.it
        placeholdit_text:   ' '   # Text to display on placehold.it image - space ' ' leaves a blank image, '' shows the size.
    
        # include controller in generated image URLs?
        use_script_name:    true
        adapter:            ImagePoolImageMagickAdapter
        # adapter:            sfGDAdapter
        adapter_options:
          # Sharpen scaled/cropped images - only works for ImagePoolImageMagickAdapter
          sharpen:      true
          # Sharpening is CPU-intensive, so you can prefix the "convert" command
          # with nice -n19 to make sure other processes get priority over the CPU
          # convert:        nice -n19 /usr/bin/convert
      
        # How should we cache files?
        cache:
          lifetime:         7776000 # 4 weeks
          class:            sfImagePoolFilesystemCache
          # RACKSPACE CLOUD FILES ADAPTER:
          # class:          sfImagePoolRackspaceCloudFilesCache
          # options: 
          #   username:     ~ # Your Username
          #   container:    ~ # Name for the container
          #   api_key:      ~
          #   auth_host:    UK # UK or US, depending on where your account is based
          # off_site_uri:   ~ # The Base URI for the container
          # off_site_ssl_uri: ~ # The Base SSL URI for the container
    
#### Rackspace Cloud files

If you want to use the Rackspace cloud to store your image pool thumbnails you will need to add the dependancy specified in _Optional Dependencies_ and follow the instructions.

Then you can run `./symfony rackspace:initialise` and fill in your settings at the prompt.  This will generate the correct settings in your project's `config/app.yml` file 
(and create the file if it doesn't exist).  This saves you manually creating the settings as above. 


### 6. Include images in templates

Use the `ImagePoolHelper` to output a single image associated with an object. In this example, the default size is used (fit to 200px width):

#### Basic

    use_helper('Asset, Url, ImagePool');
    echo pool_image_tag($object);
    
#### With transformations

Original images are retained so you may product resized or cropped versions on the fly. To scale an image so its longest edge fits within 250px by 150px:

    echo pool_image_tag($imagepoolable_object_or_sfImagePoolImage, '200x150');
    
This is the same as

    echo pool_image_tag($imagepoolable_object_or_sfImagePoolImage, '200x150', 'scale');
    
`scale` is the default transform method. If this doesn't product a good result, try using `crop`. To crop an image to 500x325:

    echo pool_image_tag($imagepoolable_object_or_sfImagePoolImage, '500x325', 'crop');
    
#### Image parameters

The fourth parameter of `pool_image_tag` is an optional array of html attributes for the resulting image tag (a là `image_tag()`).

    echo pool_image_tag($imagepoolable_object_or_sfImagePoolImage, '500x325', 'crop', array('alt' => 'Alt text here', 'title' => 'Title here', 'class' => 'class-name'));
    
If no `alt` parameter is given, the image's caption will be used. In the backend only, if no `title` parameter is given, the image's original filename will be used. 
If the transform method is `crop` then the <img> tag's `width` and `height` attributes will be set accordingly. These are not set when using the `scale` method, as the `scale` 
method fits the image to the specified dimensions and will not necessarily match them.


### 7. Placeholder

If the configuration is set to enable placeholders then when there is no image for a model or the image can't be found the placeholder will be used.  This requires it to be in the 
database.  Use the sample fixtures file, and create `placeholder.jpg` in the `image-pool` folder for this purpose.


### 8 .htaccess tweaks

We need to set the `mod_rewrite` rules to serve the local copy if it exists, or route the request through the controller to generate the crop (and then cache it):
    
    <IfModule mod_rewrite.c>
      RewriteEngine On
  
      # uncomment the following line, if you are having trouble
      # getting no_script_name to work
      RewriteBase /
  
      # Don't route files ending in .something
      RewriteCond %{REQUEST_URI} \..+$
      RewriteCond %{REQUEST_URI} !\.html$
      RewriteCond %{REQUEST_URI} !image-pool
      RewriteRule .* - [L]
    
      # we check if the .html version is here (caching)
      RewriteRule ^$ index.html [QSA]
      RewriteRule ^([^.]+)$ $1.html [QSA]
  
      # no, so we redirect to our front web controller
      RewriteCond %{REQUEST_FILENAME} !-f
      RewriteRule ^(.*)$ index.php [QSA,L]
  
    </IfModule>

Additionally, to improve performance, dynamically resized and cropped images are created once and then served from the filesystem (if caching on filesystem). 
To ensure this works when accessing the website via controllers, add the following lines to `web/.htaccess`:

    RewriteCond "%{DOCUMENT_ROOT}$2" -f
    RewriteRule (\w+\.php)(/image-pool/(crop|scale)/\d{1,3}/\d{1,3}/[\w\-]+\.(gif|jpe?g|png)) $2 [NC,L]
    
*Note*: the 'image-pool' part of the rewrite rule above refers to the default location where uploaded/transformed images are saved. This can be configured in the 
plugin's sfImagePoolPluginConfiguration class. If changed the above rule would also need changing.   


### Optional extensions

#### 1. Add sfImagePool to MooEditable text areas

To add a HTML editor (see _Requirements_) with image pool for image insertion, use the following method (or take the code in the method and amend to it) - in the form class.  
It requires the extensions' javascripts and stylesheets to be included in form

    class MyModelForm extends BaseMyModelForm
    {
      public function configure()
      {
        $widgetName = 'summary';  
        $restrictToTag = '';
        sfImagePoolUtil::addImagePoolMooEditable($this, $widgetName, $restrictToTag);
      }
    
	  public function getJavaScripts()
      {
        $js = parent::getJavascripts();
   
		return array_merge($js, array('/sfImagePoolPlugin/js/MooEditable.ImagePool.js'));
      }
   
      public function getStylesheets()
      {
         $css = parent::getStylesheets();
   
         return array_merge($css, array('/sfImagePoolPlugin/css/MooEditable.ImagePool.css'=>'all'));
      }
    }


### Overriding Automatic Crops

Automatic may crops produce undesirable results (e.g. heads chopped off when cropping a portrait image to landscape). These crops may be overridden by uploading a custom replacement via the 
`sfImagePoolAdmin` module. The `image` action of `sfImagePool` will use always use manual crops in place of automatic ones if there's one available at the requested dimensions.

For example:

 * Upload a brand new image
 * Output in a template using the crop method at 300x200 is producing a bad crop for this image
 * Manually create a crop at 300x200 in your editor of choice
 * Find the original image in `sfImagePoolAdmin` module, upload your manual crop
 * Plugin will no longer create an image for crops at 300x200, but will use your image.
 
 
Testing
-------

Please note that for test environments the option `tagging` is overwritten in `PluginsfImagePoolImage` to be set as `false` - this is to ensure that tests do not depend on 
additional plugins being installed.

The `sfImagePoolRackCloudCache` test requires Rackspace Cloud credentials, therefore this test can only be run successfully in projects with these credentials set in in 
`app.yml` - see _5. Customise plugin options_.