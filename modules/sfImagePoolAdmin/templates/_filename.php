<?php use_helper('ImagePool') ?>
<?php use_stylesheet('/sfImagePoolPlugin/css/sfImagePool.css') ?>

<div class="sf_admin_form_row sf_admin_text sf_admin_form_field_filename">
    <?php echo $form['filename']->renderLabel() ?>
    <div class="content">
        <?php echo pool_image_tag($form->getObject(), '200', 'scale', array('class' => 'image-preview')) ?>
        <br /><br />
        
        <?php echo $form['filename']->renderError() ?>
        <?php echo $form['filename']->render() ?>
    </div>
    <div class="help"><?php echo $form['filename']->renderHelp() ?></div>
</div>