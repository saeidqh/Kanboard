<div class="page-header">
    <h2><?= t('New department') ?></h2>
    <ul>
        <li>
            <?= $this->url->link(
                '← ' . t('Back to Roles'),
                'HierarchicalRoleController',
                'index',
                array('plugin' => 'HierarchicalRoles', 'project_id' => $project['id'])
            ) ?>
        </li>
        <li>
            <?= $this->url->link(
                t('Departments list'),
                'DepartmentController',
                'index',
                array('plugin' => 'AhvazTaskFields', 'project_id' => $project['id'])
            ) ?>
        </li>
    </ul>
</div>

<form method="post" action="<?= $this->url->href('DepartmentController', 'save', array('plugin' => 'AhvazTaskFields', 'project_id' => $project['id'])) ?>" autocomplete="off">
    <?= $this->form->csrf() ?>

    <?= $this->form->label(t('Name'), 'name') ?>
    <?= $this->form->text('name', $values, $errors, array('autofocus', 'required', 'maxlength="255"')) ?>

    <?= $this->form->checkbox('is_active', t('Active'), 1, isset($values['is_active']) ? (bool)$values['is_active'] : true) ?>

    <?= $this->modal->submitButtons() ?>
</form>
