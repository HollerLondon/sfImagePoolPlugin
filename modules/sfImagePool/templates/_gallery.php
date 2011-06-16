<?php use_helper('sfImagePool') ?>

<?php if($object->allowSelectMultiple()): ?>
    <?php if(count($object->getPoolImages()->getUnfeatured()) > 1): ?>
        <ul class="gallery">
        <?php foreach($object->getPoolImages()->getUnfeatured() as $i): ?>
            <?php if($i['filename']): ?>
            <li><?php echo link_to(sf_image_pool_image($i, '50x50'), sf_image_pool_image_url($i, '468x315', 'crop')) ?></li>
            <?php endif ?>
        <?php endforeach ?>
        </ul>
    <?php endif ?>

    <div class="featured-image"><?php echo sf_image_pool_image($object->getFeaturedImage(), '468x315') ?></div>
    
<?php else: ?>
    <?php echo sf_image_pool_image($object, '468x315') ?>

<?php endif ?>       