<?php if ($this->hierarchicalRoleHelper->canManageOrgStructure($project['id'])): ?>
<li <?= $this->app->checkMenuSelection('HierarchicalRoleController') ?>>
    <?= $this->url->link(t('Organizational Structure'), 'HierarchicalRoleController', 'index', array('plugin' => 'HierarchicalRoles', 'project_id' => $project['id'])) ?>
</li>
<?php endif ?>
