<?php use_helper('ImagePool') ?>
<?php use_stylesheet('/sfImagePoolPlugin/css/sfImagePool.css') ?>

<div class="sf_admin_form_row sf_admin_text sf_admin_form_field_filename">
    <?php echo $form['filename']->renderLabel() ?>
    <div class="content">
    
      <div class="featured-image" style="float: left;">
        <?php echo pool_image_tag($form->getObject(), '200', 'scale', array('class' => 'image-preview')) ?>
        <br /><br />
        
        <?php echo $form['filename']->renderError() ?>
        <?php echo $form['filename']->render() ?>
      </div>
      
      <div class="sizes" style="float: left; margin-left: 10px;"><?php include_component('sfImagePoolAdmin', 'sizes', array('image'=>$form->getObject())); ?></div>
      <br class="clear" />
    </div>
    <div class="help"><?php echo $form['filename']->renderHelp() ?></div>
</div>
