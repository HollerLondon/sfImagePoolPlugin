<?php use_helper('ImagePool') ?>

<div id="sf_admin_container">
  <h1>Image Pool</h1>

  <div id="sf_admin_content">
  
    <h2>Upload Custom Crop</h2>
    <p>Your upload will override any automatic crop that would be performed to the same dimensions (as your image).</p>
    
    <div class="sf_admin_form">
      <?php echo $form->renderFormTag(url_for('sf_image_pool_upload_crop', $image)) ?>
        <fieldset>
          <p><?php echo pool_image_tag($image, '200', 'scale', array('class' => 'image-preview')) ?></p>
          
          <div class="sf_admin_form_row sf_admin_text sf_admin_form_field_cropped_image<?php if ($form['cropped_image']->hasError()) echo ' errors'; ?>">
            <?php echo $form['cropped_image']->renderError(); ?>
          
            <div>
              <label for="modules_youtube_id"><?php echo $form['cropped_image']->renderLabel(); ?></label>
              <div class="content">
                <?php echo $form['cropped_image']->render(); ?>
              </div>
          	</div>
          </div>
          
          <?php echo $form->renderHiddenFields(); ?>
        </fieldset>
        
        <ul class="sf_admin_actions">
          <li class="sf_admin_action_delete"><?php echo link_to('Cancel', 'sfImagePoolAdmin/index') ?></li>  
          <li class="sf_admin_action_save"><input type="submit" value="Upload"></li>
        </ul>
      </form>
    
    </div>
  
  </div>

</div>