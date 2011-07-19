<?php use_helper('ImagePool') ?>

<h2>Upload Custom Crop</h2>
<p>Your upload will override any automatic crop that would be performed to the same dimensions (as your image).</p>

<div class="sf_admin_form">
<?php echo $form->renderFormTag(url_for('sf_image_pool_upload_crop', $image)) ?>
    <fieldset>
        <p><?php echo pool_image_tag($image, '200', 'scale', array('class' => 'image-preview')) ?></p>
        <table>
          <?php echo $form ?>
        </table>
        <div>
            <input type="submit" value="Upload" />
            <br />
            <?php echo link_to('Cancel', 'sfImagePoolAdmin/index') ?>
        </div>
    </fieldset>
</form>
