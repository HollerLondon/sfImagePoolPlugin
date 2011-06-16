<?php use_helper('sfImagePool') ?>

<?php if($object->allowSelectMultiple()): ?>
    <div class="featured-image"><?php echo pool_image_tag($object->getFeaturedImage(), '720x250', 'crop') ?></div>

    <?php if(count($object->getPoolImages()->getUnfeatured())): ?>
        <ul class="sf_image_pool_gallery_admin">
        <?php foreach($object->getPoolImages()->getUnfeatured() as $i): ?>
            <?php if($i['filename']): ?>
            <li><?php echo link_to(pool_image_tag($i, '50x50'), pool_image_uri($i, '720x250', 'crop')) ?></li>
            <?php endif ?>
        <?php endforeach ?>
        </ul>
    <?php endif ?>
    
<?php else: ?>
    <?php echo pool_image_tag($object, '720x250', 'crop') ?>

<?php endif ?>       