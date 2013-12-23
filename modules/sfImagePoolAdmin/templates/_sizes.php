<h3>Image crops</h3>

<ul style="margin-top: 0;">
  <?php foreach ($crops as $size => $crop) : ?>
    <li><?php echo $size; ?>:  
      <?php echo image_tag('/sfDoctrinePlugin/images/tick.png'); ?>
      <?php echo link_to('[view crop]', pool_image_uri($crop->Image, $size, 'crop', true), array('target'=>'_blank')); ?>
    </li>
  <?php endforeach; ?>
</ul>

<?php echo link_to('[edit/ upload crops]', 'sf_image_pool_upload_crop', $image, array('target'=>'_blank')); ?>
