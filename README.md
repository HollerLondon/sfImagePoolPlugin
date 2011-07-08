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

 * Doctrine 1.x
 * Symfony 1.3/4
 * [sfThumbnailPlugin](http://www.symfony-project.org/plugins/sfThumbnailPlugin)
 * [sfDoctrineActAsTaggablePlugin](http://www.symfony-project.org/plugins/sfDoctrineActAsTaggablePlugin) (but only if tagging: true) in the options section of the model definition for sfImagePoolImage in schema.yml
 * ImageMagick
 * MooTools More:
  * Fx.Reveal
  
  
Setup
-----

### 1. Enable the plugin ###


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

#### Enable tagging (optional) #### 

Decide whether tagging should be enabled (requires `sfDoctrineActAsTaggablePlugin`) - add in doctrine/schema.yml

  sfImagePoolImage:
    options:
      tagging:  true

#### Add behaviour #### 

  ModelName:
    actAs: [sfImagePoolable]
    ...
    
#### Options #### 

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

#### Add the widget #### 

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

#### Ensure images are associated, extend sfImagePoolableBaseForm #### 

The plugin handles all image association when saving a form that has the sfImageChooserWidget widget embedded. To setup simply alter your BaseFormDoctrine class:

  abstract class BaseFormDoctrine extends sfImagePoolableBaseForm
  {
  ...
