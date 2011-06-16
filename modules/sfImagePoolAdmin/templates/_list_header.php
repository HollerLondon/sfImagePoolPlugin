<?php use_helper('sfImagePool') ?>

<?php if($sf_user->hasFlash('uploaded_file')): ?>
    <?php echo pool_image_tag($sf_user->getFlash('image'), $sf_user->getFlash('uploaded_file_dimensions'), 'crop', array('class' => 'image-preview')) ?>
<?php endif ?>

<p>Mouse over an image for options.</p>