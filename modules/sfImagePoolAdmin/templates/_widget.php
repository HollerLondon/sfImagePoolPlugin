<?php use_helper('ImagePool'); ?>

<div id="<?php echo $id; ?>" class="imageChooser<?php if ($multiple) echo ' multiple'; ?>">
  <div class="selectedImage">
    <?php foreach ($images as $i): ?>
      <?php echo pool_image_tag($i, '100', 'crop', array('id'=>'sf_image_pool_image_'+$i->getPrimaryKey())); ?>
      <input type="hidden" name="<?php echo $name; ?>[]" value="<?php echo $i->getPrimaryKey(); ?>" />
    <?php endforeach; ?>
  </div>
  
  <input type="hidden" name="<?php echo $name; ?>[]" value="" class="sf-image-id" />
  
  <div class="thumbnailsContainer">
    <?php include_partial('sfImagePoolAdmin/chooser_pagination', array(
        'pager'        => $pager,
        'paginationId' => 'pagination',
        'object'       => $object,
        'id'           => $id,
        'multiple'     => $multiple,
        'tag'          => $tag
    )); ?>
  </div>
  
  <a href="#" title="Display thumbnails of all images" class="toggleThumbnails">Show Images</a>
</div>
