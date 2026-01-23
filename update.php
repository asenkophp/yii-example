<h2><?php echo $model->isNewRecord ? 'Create Warehouse' : 'Edit Warehouse'; ?></h2>

<?php $form = $this->beginWidget('CActiveForm', [
    'id' => 'warehouse-form',
    'enableAjaxValidation' => true,
    'clientOptions' => [
        'validateOnSubmit' => true,
        'validateOnChange' => true,
    ],
]); ?>

<div class="form-group">
    <?php echo $form->labelEx($model, 'name'); ?>
    <?php echo $form->textField($model, 'name', [
        'class' => 'form-control',
        'placeholder' => 'Enter warehouse name',
        'maxlength' => 255,
    ]); ?>
    <?php echo $form->error($model, 'name'); ?>
</div>

<div class="form-group">
    <?php echo $form->labelEx($model, 'location'); ?>
    <?php echo $form->textField($model, 'location', [
        'class' => 'form-control',
        'placeholder' => 'Enter warehouse location',
        'maxlength' => 255,
    ]); ?>
    <?php echo $form->error($model, 'location'); ?>
</div>

<div class="form-group">
    <?php echo $form->labelEx($model, 'capacity'); ?>
    <?php echo $form->textField($model, 'capacity', [
        'class' => 'form-control',
        'placeholder' => 'Enter capacity (numbers only)',
        'maxlength' => 10,
        'pattern' => '\d*',
        'id' => 'capacity-field',
    ]); ?>
    <?php echo $form->error($model, 'capacity'); ?>
    <small id="capacity-info" class="text-muted">Current total capacity: 0</small>
</div>

<div class="form-group">
    <?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save', [
        'class' => 'btn btn-primary',
    ]); ?>
</div>

<?php $this->endWidget(); ?>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    const capacityInput = document.getElementById('capacity-field');
    const capacityInfo = document.getElementById('capacity-info');

    capacityInput.addEventListener('input', function() {
        // Mask: only numbers are allowed
        this.value = this.value.replace(/\D/g, '');
        // Dynamic display of current total capacity
        const currentCapacity = parseInt(this.value || 0, 10);
        capacityInfo.textContent = 'Current total capacity: ' + currentCapacity;
    });
});
</script>
