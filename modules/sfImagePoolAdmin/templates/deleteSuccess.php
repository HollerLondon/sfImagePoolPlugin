<?php use_helper('ImagePool') ?>

<div id="sf_admin_container">

  <h1>Delete this image?</h1>
  
  <div id="sf_admin_content">
    <?php echo pool_image_tag($image, '300'); ?>
    <p><?php echo $image['original_filename']; ?></p>
    
    <?php if (count($models)): ?>
      <h2 style="color:red !important;">Careful!</h2>
      <p>This will affect:</p>
      
      <ul>
        <?php foreach ($models as $model_name => $usage_count) : ?>
        <li><?php echo $usage_count; ?> <?php echo strtolower(sfInflector::humanize($model_name)) . (1 != $usage_count ? 's' : ''); ?></li>
        <?php endforeach; ?>
      </ul>
      
    <?php endif; ?>
    
    <ul class="sf_admin_actions">
      <li class="sf_admin_action_delete"><?php echo link_to('Delete', 'sf_image_pool_deleteused', $image, array('post' => true, 'method' => 'delete')) ?></li>
      <li class="sf_admin_action_list"><strong><?php echo link_to('Cancel', 'sf_image_pool_image') ?></strong></li>
    </ul>
    
  </div>
</div>
