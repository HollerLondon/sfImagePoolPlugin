<?php use_helper('ImagePool') ?>

<?php 
// Send through extra optional parameters to AJAX
$extra = array('chooser_id' => $id);
$object = $sf_data->getRaw('object'); // Otherwise class is sfOutputEscaperIteratorDecorator ;)

if ($object && $object->id) 
{
  $extra = $extra + array('class'=>get_class($object), 'class_id'=>$object->getId());
}

if ($sf_data->offsetExists('tag')) 
{
  $tag = $sf_data->getRaw('tag');       // Hack if no object for MooEditable
  if ($tag && !empty($tag)) $extra['tag'] = $tag;
}
?>

<?php foreach ($pager->getResults() as $i): ?>
  <div class="pool-image">
    <label>
      <?php echo pool_image_tag($i, '100', 'crop', array('title' => $i['filename'], 'rel' => $i['id'])) ?>
    </label>
  </div>
<?php endforeach ?>

<p class="<?php echo $paginationId ?>">
  <?php if ($pager->haveToPaginate()): ?>
    <?php
    if ($pager->getPage() !== $pager->getFirstPage())
    {
      echo link_to('&laquo; First', 'sf_image_pool_chooser', array('page' => $pager->getFirstPage()) + $extra);
      echo ' | ';
      echo link_to('&#139; Previous', 'sf_image_pool_chooser', array('page' => $pager->getPreviousPage()) + $extra);
      echo ' | ';
    }
    
    foreach ($pager->getLinks(10) as $link)
    {
      // Link if it's not the currrent page :)
      echo link_to_if($link !== $pager->getPage(), $link, 'sf_image_pool_chooser', array('page' => $link) + $extra);
      echo ' | ';
    }
    
    if ($pager->getNextPage() !== $pager->getLastPage())
    {
      echo link_to('Next &#155;', 'sf_image_pool_chooser', array('page' => $pager->getNextPage()) + $extra);
      echo ' | ';
      echo link_to('Last &raquo;', 'sf_image_pool_chooser', array('page' => $pager->getLastPage()) + $extra);
    }
    ?>
    
    <br />
  <?php endif ?>
  
  Displaying <?php echo $pager->getFirstIndice() ?> - <?php echo $pager->getLastIndice() ?> of <?php echo $pager->getNbResults() ?> images
</p>

<ul class="sf_admin_actions"><li class="sf_admin_action_new"><?php echo link_to('Upload a new image', 'sf_image_pool_chooser_upload', $extra, array('class'=>"upload_new_image")); ?></li></ul>
<input type="hidden" id="<?php echo $id; ?>_page_1" value="<?php echo url_for('sf_image_pool_chooser', array('page' => 1) + $extra); ?>" />