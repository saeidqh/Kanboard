<div class="page-header">
    <h2><?= t('Remove Role') ?></h2>
</div>

<div class="confirm">
    <p class="alert alert-info">
        <?= t('Do you really want to remove the role "%s"?', $this->text->e($role['title'])) ?>
    </p>
    <p><?= t('Users assigned to this role will be unassigned. Child roles will be moved up.') ?></p>

    <?= $this->modal->confirmButtons(
        'HierarchicalRoleController',
        'remove',
        array('plugin' => 'HierarchicalRoles', 'project_id' => $project['id'], 'role_id' => $role['id'])
    ) ?>
</div>
