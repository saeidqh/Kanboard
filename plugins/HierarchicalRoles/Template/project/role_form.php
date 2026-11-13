<style>
.hr-role-form .form-columns {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
}
.hr-role-form .form-column {
    flex: 1 1 260px;
    min-width: 240px;
}
.hr-role-form label {
    font-weight: 600;
    margin-top: 12px;
    display: block;
    color: #333;
}
.hr-role-form input[type="text"],
.hr-role-form input[type="number"],
.hr-role-form select,
.hr-role-form textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 8px 10px;
    border: 1px solid #cfd8dc;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color .15s, box-shadow .15s;
}
.hr-role-form input:focus,
.hr-role-form select:focus,
.hr-role-form textarea:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52,152,219,.15);
    outline: none;
}
.hr-role-form .form-help {
    color: #78909c;
    font-size: 12px;
    margin: 4px 0 0;
}
.hr-role-form .form-actions {
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid #eceff1;
}
.hr-role-form .btn-blue {
    padding: 8px 18px;
    border-radius: 6px;
}
.hr-role-form .hr-badge {
    display: inline-block;
    background: #e3f2fd;
    color: #1565c0;
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 10px;
    margin-left: 8px;
    vertical-align: middle;
}
.hr-role-form .page-header h2 {
    margin-bottom: 4px;
}
.hr-role-form .hr-subtitle {
    color: #90a4ae;
    font-size: 13px;
    margin: 0 0 16px;
}
</style>

<div class="hr-role-form">
    <div class="page-header">
        <h2>
            <?= $this->text->e($title) ?>
            <?php if (empty($values['id'])): ?>
                <span class="hr-badge"><?= t('New') ?></span>
            <?php else: ?>
                <span class="hr-badge"><?= t('Edit') ?></span>
            <?php endif ?>
        </h2>
        <p class="hr-subtitle"><?= t('Define a position in the project organization tree.') ?></p>
    </div>

    <form method="post"
          action="<?= $this->url->href(
              'HierarchicalRoleController',
              isset($values['id']) ? 'update' : 'save',
              array('plugin' => 'HierarchicalRoles', 'project_id' => $project['id'])
          ) ?>"
          autocomplete="off"
          class="modal-form">

        <?= $this->form->csrf() ?>

        <?php if (!empty($values['id'])): ?>
            <?= $this->form->hidden('id', $values) ?>
        <?php endif ?>
        <?= $this->form->hidden('project_id', $values) ?>

        <div class="form-columns">
            <div class="form-column">
                <?= $this->form->label(t('Role Title'), 'title') ?>
                <?= $this->form->text('title', $values, $errors, array(
                    'required',
                    'autofocus',
                    'maxlength="150"',
                    'placeholder="'.t('e.g. Mech Manager').'"'
                )) ?>

                <?= $this->form->label(t('Parent Role'), 'parent_id') ?>
                <?= $this->form->select('parent_id', $parents, $values, $errors) ?>
                <p class="form-help"><?= t('Leave empty for top-level role (Level 1). Level is calculated automatically.') ?></p>
            </div>

            <div class="form-column">
                <?= $this->form->label(t('Description'), 'description') ?>
                <?= $this->form->textarea('description', $values, $errors, array(
                    'rows="5"',
                    'placeholder="'.t('Optional – what this role is responsible for').'"'
                )) ?>

                <?= $this->form->label(t('Sort Position'), 'position') ?>
                <?= $this->form->number('position', $values, $errors, array('min="0"', 'placeholder="0"')) ?>
                <p class="form-help"><?= t('Lower number appears higher in the list.') ?></p>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-blue"><?= t('Save role') ?></button>
            <?= t('or') ?>
            <?= $this->url->link(t('cancel'), 'HierarchicalRoleController', 'index', array(
                'plugin' => 'HierarchicalRoles',
                'project_id' => $project['id']
            ), false, 'js-modal-close') ?>
        </div>
    </form>
</div>
