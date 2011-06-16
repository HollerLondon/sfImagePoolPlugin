<?php use_helper('sfImagePool') ?>

<?php if($pager->getNbResults()): ?>
<ul id="sf_image_pool_images">
<?php foreach($pager->getResults() as $i): ?>
    <li>
        <?php /* ?><input type="checkbox" name="ids[]" value="<?php echo $i->getPrimaryKey() ?>" class="sf_admin_batch_checkbox" /><?php */ ?>
        <div class="actions">
            <?php echo $helper->linkToEdit($i, array('params' => array(), 'class_suffix' => 'edit',  'label' => 'Edit')) ?>
            <?php echo link_to('Upload Crop', 'sf_image_pool_upload_crop', $i) ?>
            <?php echo $helper->linkToDelete($i, array('params' => array(), 'confirm' => 'Are you sure?', 'class_suffix' => 'delete', 'label' => 'Delete')) ?>
        </div>
        
        <?php echo pool_image_tag($i, '100', 'crop') ?>
        <div class="filename" title="<?php echo $i['original_filename'] ?>"><?php echo $i['original_filename'] ?></div>
    </li>
<?php endforeach ?>
</ul>
<br clear="left" />

<?php if($pager->haveToPaginate()): ?>
    <?php include_partial('sfImagePoolAdmin/pagination', array('pager' => $pager)) ?>
<?php endif; ?>

<?php endif ?>

<br /><p><?php echo $pager->getNbResults(); ?> image(s) found</p>
        
<?php /* ?>
<script type="text/javascript">
// <![CDATA[ 
function checkAll()
{
  var boxes = document.getElementsByTagName('input'); for(var index = 0; index < boxes.length; index++) { box = boxes[index]; if (box.type == 'checkbox' && box.className == 'sf_admin_batch_checkbox') box.checked = document.getElementById('sf_admin_list_batch_checkbox').checked } return true;
}
// ]]> 
</script>
<?php */ ?>