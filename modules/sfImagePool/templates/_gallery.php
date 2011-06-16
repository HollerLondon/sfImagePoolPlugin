<?php use_helper('sfImagePool') ?>

<?php if($object->allowSelectMultiple()): ?>
    <?php if(count($object->getPoolImages()->getUnfeatured()) > 1): ?>
        <ul class="gallery">
        <?php foreach($object->getPoolImages()->getUnfeatured() as $i): ?>
            <?php if($i['filename']): ?>
            <li><?php echo link_to(pool_image_tag($i, '50x50'), pool_image_uri($i, '468x315', 'crop')) ?></li>
            <?php endif ?>
        <?php endforeach ?>
        </ul>
    <?php endif ?>

    <div class="featured-image"><?php echo pool_image_tag($object->getFeaturedImage(), '468x315') ?></div>
    
<?php else: ?>
    <?php echo pool_image_tag($object, '468x315') ?>

<?php endif ?>       