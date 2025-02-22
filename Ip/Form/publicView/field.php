<div class="form-group type-<?php echo $field->getTypeClass(); ?><?php if ($field->getName() != null) {
    echo " name-" . $field->getName();
} ?><?php if ($field->isRequired()) {
    echo " required";
} ?>">
    <label for="<?php echo $field->getId(); ?>">
        <?php echo esc($field->getLabel()); ?>
    </label>
    <?php echo $field->render($this->getDoctype(), \Ip\Form::ENVIRONMENT_PUBLIC); ?>
    <div class="help-error"></div>
    <?php if ($field->getNote()) { ?>
        <div class="help-block"><?php echo $field->getNote(); ?></div>
    <?php } ?>
    <?php if ($field->getHint()) { ?>
        <div class="help-hint"><?php echo $field->getHint(); ?></div>
    <?php } ?>
</div>
