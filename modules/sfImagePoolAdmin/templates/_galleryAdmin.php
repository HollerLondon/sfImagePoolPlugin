<?php use_helper('sfImagePool') ?>

<?php if($object->allowSelectMultiple()): ?>
    <div class="featured-image"><?php echo sf_image_pool_image($object->getFeaturedImage(), '720x250', 'crop') ?></div>

    <?php if(count($object->getPoolImages()->getUnfeatured())): ?>
        <ul class="sf_image_pool_gallery_admin">
        <?php foreach($object->getPoolImages()->getUnfeatured() as $i): ?>
            <?php if($i['filename']): ?>
            <li><?php echo link_to(sf_image_pool_image($i, '50x50'), sf_image_pool_image_url($i, '720x250', 'crop')) ?></li>
            <?php endif ?>
        <?php endforeach ?>
        </ul>
    <?php endif ?>
    
<?php else: ?>
    <?php echo sf_image_pool_image($object, '720x250', 'crop') ?>

<?php endif ?>       