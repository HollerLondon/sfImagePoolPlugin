<?php use_helper('sfImagePool') ?>

<div id="imageChooser" class="<?php echo $multiple ? 'multiple' : '' ?>">
    <div id="selectedImage">
    <?php foreach($images as $i): ?>
      <?php echo sf_image_pool_image($i, '100', 'crop',array('id'=>'sf_image_pool_image_'+$i->getPrimaryKey())) ?>
      <input type="hidden" name="<?php echo $name ?>[]" value="<?php echo $i->getPrimaryKey() ?>" />
    <?php endforeach ?>
    </div>
    <input type="hidden" name="<?php echo $name ?>[]" value="" id="sf-image-id" />
    <div id="thumbnailsContainer">
        <?php include_partial('sfImagePoolAdmin/chooser_pagination', array(
            'pager'        => $pager,
            'paginationId' => 'pagination',
            'object'       => $object,
            'name'         => $name,
            'multiple'     => $multiple
        )) ?>
    </div>
    
    <a href="#" title="Display thumbnails of all images" id="toggleThumbnails">Show Images</a>
</div>
