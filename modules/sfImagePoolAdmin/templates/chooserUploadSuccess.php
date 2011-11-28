<!DOCTYPE html>
<html lang="en">
  <head>
    <?php
      // Layout set to false - to include in header, set here
      use_javascript('/sfImagePoolPlugin/js/sfImageChooser.js', 'last');
      use_stylesheet('/sfImagePoolPlugin/css/image-chooser.css', 'last');
    
      include_stylesheets();
      include_stylesheets_for_form($form);
      include_javascripts();
      include_javascripts_for_form($form);
    ?>
  </head>
  <body id="iframe">
    <div id="sf_admin_content">
      <div class="sf_admin_form">
        <?php if ($success) : ?>
          <h1>Image uploaded</h1>
          <script type="text/javascript">
            parent.closeIframe();
          </script>
        <?php else : ?>
          <?php include_partial('sfImagePoolAdmin/chooser_upload', array('paginationId'  => 'pagination', 'form' => $form)); ?>
        <?php endif; ?>
      </div>
    </div>
  </body>
</html>