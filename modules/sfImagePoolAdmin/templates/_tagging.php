<?php foreach($form->getTagFields() as $name => $params): ?>
<div class="sf_admin_form_row">
    <div>
        <?php echo $form[$name]->renderLabel() ?>
        <div class="content">
            <?php echo $form[$name]->renderError() ?>
            <?php echo $form[$name]->render() ?>
        </div>
        <?php if ($help = $form[$name]->renderHelp()) : ?>
          <div class="help">
            <?php echo $help; ?>
          </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>