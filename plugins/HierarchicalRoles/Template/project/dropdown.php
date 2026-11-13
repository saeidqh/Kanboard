<?php if ($this->hierarchicalRoleHelper->canManageOrgStructure($project['id'])): ?>
<li>
    <?= $this->url->link(
        '<i class="fa fa-sitemap fa-fw"></i> '.t('Organizational Structure'),
        'HierarchicalRoleController',
        'index',
        array('plugin' => 'HierarchicalRoles', 'project_id' => $project['id'])
    ) ?>
</li>
<?php endif ?>
