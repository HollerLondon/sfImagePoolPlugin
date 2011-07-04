<?php use_helper('ImagePool') ?>

<h2>Delete this image?</h2>

<?php echo pool_image_tag($image, '300') ?>
<p><?php echo $image['original_filename'] ?></p>

<?php if(count($models)): ?>
    <h3>Careful!</h3>
    <p>This will affect:</p>
    
    <ul>
      <?php foreach($models as $model_name => $usage_count): ?>
        <li><?php echo $usage_count; ?> <?php echo strtolower(sfInflector::humanize($model_name)) . (1 != $usage_count ? 's' : ''); ?></li>
      <?php endforeach ?>
    </ul>
    
<?php endif ?>

<p>
    <?php echo link_to('Delete', 'sf_image_pool_deleteused', $image, array('post' => true, 'method' => 'delete')) ?>
    | <strong><?php echo link_to('Cancel', 'sf_image_pool_image') ?></strong>
</p>