<?php use_helper('ImagePool') ?>

<?php if ($pager->getNbResults()) : ?>
  <ul id="sf_image_pool_images">
    <?php foreach($pager->getResults() as $i): ?>
      <li class="image">
        <div class="actions">
          <ul class="image_list_actions">
            <?php echo $helper->linkToEdit($i, array('params' => array(), 'class_suffix' => 'edit',  'label' => 'Edit')) ?>
            <li><?php echo link_to('Upload Crop', 'sf_image_pool_upload_crop', $i) ?></li>
            <?php echo $helper->linkToDelete($i, array('params' => array(), 'confirm' => 'Are you sure?', 'class_suffix' => 'delete', 'label' => 'Delete')) ?>
          </ul>
        </div>
        
        <?php echo pool_image_tag($i, '100', 'crop') ?>
        <div class="filename" title="<?php echo $i['original_filename'] ?>"><?php echo $i['original_filename'] ?></div>
      </li>
    <?php endforeach ?>
  </ul>
  <br clear="left" />
  
  <?php if ($pager->haveToPaginate()): ?>
    <?php include_partial('sfImagePoolAdmin/pagination', array('pager' => $pager)) ?>
  <?php endif; ?>

<?php endif ?>

<br /><p><?php echo $pager->getNbResults(); ?> image(s) found</p>
