<?php 
// Send through extra optional parameters to AJAX
if ($sf_params->has('class')) 
{
  $extra = array('class'=>$sf_params->get('object'), 'class_id'=>$sf_params->get('object_id'));
}
else $extra = array();

if ($sf_params->has('tag')) 
{
  $tag = $sf_params->getRaw('tag');       // Hack if no object for MooEditable
  if ($tag && !empty($tag)) $extra['tag'] = $tag;
}
?>
<div id="content">
  <div id="sf_admin_container">

    <h1>Upload a new image</h1>
    
    <?php echo $form->renderFormTag(url_for('sf_image_pool_chooser_upload', $extra), array('id'=>'image_pool_upload')); ?>
        <?php echo $form->renderHiddenFields(); ?>
        
        <div class="sf_admin_form_row sf_admin_text">
          <?php echo $form['filename']->renderLabel(); ?><div class="content"><?php echo $form['filename']->render(); ?><?php echo $form['filename']->renderError(); ?></div>
        </div>
        
        <div class="sf_admin_form_row sf_admin_text">
          <?php echo $form['caption']->renderLabel(); ?><div class="content"><?php echo $form['caption']->render(); ?><?php echo $form['caption']->renderError(); ?></div>
        </div>
        
        <?php if (isset($form['image_tags'])) : ?> 
          <div class="sf_admin_form_row sf_admin_text">
            <?php echo $form['image_tags']->renderLabel(); ?>
            <div class="content"><?php echo $form['image_tags']->render(); ?><?php echo $form['image_tags']->renderError(); ?></div>
            <?php if (isset($extra['tag'])) : ?><div class="help">This has been pre-populated with the tag restrictions; choose the ones you need and add as appropriate.</div><?php endif; ?>
          </div>
        <?php endif; ?>
      
      <ul class="sf_admin_actions">
        <li class="sf_admin_action_save"><input type="submit" name="upload_image" id="upload_image" value="Upload image" /></li>
        <li><input type="button" name="cancel_upload" id="cancel_upload" value="Cancel" /></li>
      </ul>
    </form>
  </div>
</div>
