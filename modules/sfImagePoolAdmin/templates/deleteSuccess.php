<?php use_helper('sfImagePool') ?>

<h2>Delete this image?</h2>

<?php echo sf_image_pool_image($image, '300') ?>
<p><?php echo $image['original_filename'] ?></p>

<?php if(count($models)): ?>
    <h3>Careful!</h3>
    <p>This will affect:</p>
    
    <?php foreach($models as $model_name => $usage_count): ?>
        <?php printf('%s %s', $usage_count, strtolower(sfInflector::humanize($model_name))) ?>
    <?php endforeach ?>
    
<?php endif ?>

<p>
    <?php echo link_to('Delete', 'sf_image_pool_deleteused', $image, array('post' => true, 'method' => 'delete')) ?>
    | <strong><?php echo link_to('Cancel', 'sf_image_pool_image') ?></strong>
</p>