sfImagePoolPlugin
=================

@author:    Ben Lancaster (<benlancaster@holler.co.uk>)  
@author:    Jo Carter (<jocarter@holler.co.uk>)

@version:   2.0


Introduction
------------

A plugin that provides a image pool for attaching one or more images to an object. Images can then be resized/cropped on the fly and are cached 
on the filesystem (or on the edge, depending on preference). Images can be tagged, and a model's use of images restricted to a specific tag. 
Custom crops may be uploaded, which are then used instead of an automatically generated crop for those dimensions.

The plugin is comprised of:

 * `sfImagePoolable` Doctrine behaviour
 * Admin generator module `sfImagePoolAdmin` for adding and removing the to the image pool, editing images (tags, name, caption, description) and uploading custom crops.
 * `sfImagePool` module for image transformations and output
 * `sfImagePoolHelper` for easy inclusion of a model's image(s) in templates
 * `sfWidgetFormImagePoolChooser` widget for selecting images and a featured image (when multiple images are being selected)
 * Plugin for `sfMooToolsFormExtraPlugin` to enable image pool images to be added into a rich text area


Requirements
------------

### Default

 * Doctrine 1.x
 * Symfony 1.3/1.4
 * [sfThumbnailPlugin](http://www.symfony-project.org/plugins/sfThumbnailPlugin)
 * ImageMagick
 * MooTools More:
  * Fx.Reveal
  
### Dependant on settings

 * [sfDoctrineActAsTaggablePlugin](http://www.symfony-project.org/plugins/sfDoctrineActAsTaggablePlugin) (but only if tagging: true) in the options section of the model definition for sfImagePoolImage in schema.yml
 * [sfMooToolsFormExtraPlugin](https://github.com/HollerLondon/sfMooToolsFormExtraPlugin) - if images in rich text areas are required - see 5.
  
Setup
-----

### 1. Enable the plugin ###

In `ProjectConfiguration`


### 2. Enable modules ###

Typically, `sfImagePoolAdmin` in the backend app, and `sfImagePool` in all apps where you need to output images.

Every application that'll handle images from the image pool should have the `sfImagePool` module enabled in the app's settings.yml (or globally in the project's settings.yml):

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
      
      
### 3. Add behaviour to model(s) in schema.yml ###

#### Enable tagging (optional)

Decide whether tagging should be enabled (requires `sfDoctrineActAsTaggablePlugin`) - add in doctrine/schema.yml

    sfImagePoolImage:
      options:
        tagging:  true

#### Add behaviour

    ModelName:
      actAs: [sfImagePoolable]
      ...
    
#### Options

You can specify whether the model should be allowed to have multiple images, or just a single image (default) by using the multiple option. The first image selected for the model
will become the 'featured' image.

    ModelName:
      actAs:
        sfImagePoolable: { multiple: true }    
        ...
    
To restrict a model's use of images to those with the tag `foo`, use the tag option.

    ModelName:
      actAs:
        sfImagePoolable: { tag: foo }    
        ...
    
To have associated images and files deleted when your object is deleted, set shared_images option to false (default is true). When true and your object is deleted, 
only the lookup entries are deleted, images objects and files are left untouched (i.e. remain "in the pool").

    ModelName:
      actAs:
        sfImagePoolable: { shared_images: false }    
        ...
 
    
### 4. Modify forms to use the image chooser widget ###

#### Add the widget

To associate images with a model, the sfWidgetFormImagePoolChooser widget must be added to the model's form. The validator is required so that Symfony doesn't 
reject the values submitted by the additional widget.

    class SomethingForm extends BaseSomethingForm
    {
      public function configure()
      {
        sfImagePoolUtil::addImageChooser($this);
      }
    }

*Note:* the form's object is passed to the widget so that it may fetch currently associated images and any sfImagePoolable options specific to that model.

#### Ensure images are associated, extend sfImagePoolableBaseForm

The plugin handles all image association when saving a form that has the sfImageChooserWidget widget embedded. To setup simply alter your BaseFormDoctrine class:

    abstract class BaseFormDoctrine extends sfImagePoolableBaseForm
    {
      ...


### 5. Add sfImagePool to MooEditable textareas ###

To add a HTML editor (see Requirements) with image pool for image insertion, use the following method (or take the code on the method and add to it) - in the form class

    class SomethingForm extends BaseSomethingForm
    {
        public function configure()
        {
            $widgetName = 'summary';  
            $restrictToTag = '';
            sfImagePoolUtil::addImagePoolMooEditable($this, $widgetName, $restrictToTag);
        }
    }
   
   
### 6 .htaccess tweaks ###

@TODO: Add Nginx config - http://wiki.nginx.org/HttpRewriteModule

We need to set the mod_rewrite rules to serve the local copy if it exists, or route the request through the controller to generate the crop (and then cache it):
    
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
To ensure this works when accessing the website via controllers, add the following lines to web/.htaccess:

    RewriteCond "%{DOCUMENT_ROOT}$2" -f
    RewriteRule (\w+\.php)(/image-pool/(crop|scale)/\d{1,3}/\d{1,3}/[\w\-]+\.(gif|jpe?g|png)) $2 [NC,L]
    
*Note*: the 'image-pool' part of the rewrite rule above refers to the default location where uploaded/transformed images are saved. This can be configured in the 
plugin's sfImagePoolPluginConfiguration class. If changed the above rule would also need changing.   


### 7. Customise plugin options ###

@TODO: Update with new caching options

The following options may be overridden in your app.yml files:

    sf_image_pool:
      cache_lifetime:     7776000 # three months
      chooser_per_page:   24
      
      mimes:              [image/jpeg, image/jpg, image/png, image/pjpeg, 'image/x-png']
  
      # Maximum upload size to accept
      maxfilesize:        5242880
      
      # Folder within the web/ folder to store crops
      folder:             image-pool
  
      placeholders:       false # If true, use file placeholder.jpg if an image can't be found
      use_placeholdit:    false # if true, returns handy placeholder images from placehold.it
      
      # include controller in generated image URLs?
      use_script_name:    false
      adapter:            ImagePoolImageMagickAdapter
      adapter_options:
        sharpen:      true
        # You can prefix the "convert" command with nice -n19 to make it a bit more CPU friendly
        # convert:        nice -n19 /usr/bin/convert
        
      # How should we cache files?
      cache:
        class:            sfImagePoolFilesystemCache
        # class:          sfImagePoolRackspaceCloudFilesCache
        # class:          sfImagePoolAmazonS3Cache
        lifetime:         7776000 # 4 weeks
      # cache adapter options
      cache_adapter:      {}
 
    
### 8. Include images in templates ###

Use the `ImagePoolHelper` to output a single image associated with an object. In this example, the default size is used (fit to 200px width):

#### Basic

    use_helper('ImagePool');
    echo pool_image_tag($object);
    
#### Transformed

Original images are retained so you may product resized or cropped versions on the fly:. To output an image fit to 250px by 150px:

    echo pool_image_tag($object, '200x150');
    
This is the same as

    echo pool_image_tag($object, '200x150', 'scale');
    
scale is the default transform method. If this doesn't product a good result, try using crop. To crop an image to 500x325:

    echo pool_image_tag($object, '500x325', 'crop');
    
#### Image parameters

The sfImagePoolHelper accepts an array of arguments:

    echo pool_image_tag($object, '500x325', 'crop', array('alt' => 'Alt text here', 'title' => 'Title here', 'class' => 'class-name'));
    
If no `alt` parameter is given, the image's caption will be used. In the backend only, if no `title` parameter is given, the image's original filename will be used. 
If the transform method is `crop` then the <img> tag's `width` and `height` attributes will be set accordingly. These are not set when using the `scale` method, as the `scale` 
method fits the image to the specified dimensions and will not necessarily match them.


### Overriding Automatic Crops

Sometimes an automatic crop will not focus on the correct area of an image. These crops may be overridden by uploading a custom replacement via the 
`sfImagePoolAdmin` module. The size of your crop will be detected and it will automatically be output instead of an automatic crop.

For example:

 * Upload a brand new image
 * Output in a template using the crop method at 300x200 is producing a bad crop for this image
 * Create a manual 300x200 crop using Photoshop
 * Find the original image in `sfImagePoolAdmin` module, upload your manual crop
 * Plugin will no longer create an image for crops at 300x200, but will use your image.
 