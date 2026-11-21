<?php
$projectId  = isset($project['id']) ? (int)$project['id'] : (isset($values['project_id']) ? (int)$values['project_id'] : 0);
$reference  = isset($values['reference']) ? $values['reference'] : '';
$categoryId = isset($values['category_id']) ? (int)$values['category_id'] : 0;
?>
<div class="form-column" data-ahvaz-field="definition_of_done">
    <label for="form-definition_of_done">
        <?= t('Definition of Done') ?> <span style="color:#c00;">*</span>
    </label>
    <?= $this->form->textarea(
        'definition_of_done',
        $values,
        $errors,
        array(
            'rows'        => 3,
            'required'    => 'required',
            'placeholder' => t('What must be true for this task to be considered done?'),
            'id'          => 'form-definition_of_done',
        )
    ) ?>
    <p class="form-help"><?= t('This field is mandatory. Describe the acceptance criteria / Definition of Done.') ?></p>
</div>
<input type="hidden" id="ahvaz-project-id" value="<?= $projectId ?>">
<input type="hidden" id="ahvaz-current-category" value="<?= $categoryId ?>">
<input type="hidden" id="ahvaz-current-reference" value="<?= $this->text->e($reference) ?>">
<input type="hidden" id="ahvaz-form-key" value="task_create">
