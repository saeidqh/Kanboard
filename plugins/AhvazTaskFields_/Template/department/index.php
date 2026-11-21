<div class="page-header">
    <h2><?= t('Departments') ?></h2>
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
            <?= $this->modal->medium(
                'plus',
                t('New Department'),
                'DepartmentController',
                'create',
                array('plugin' => 'AhvazTaskFields', 'project_id' => $project['id'])
            ) ?>
        </li>
    </ul>
</div>

<?php if (empty($departments)): ?>
    <p class="alert"><?= t('There is no department yet.') ?></p>
<?php else: ?>
    <table class="table-striped table-scrolling">
        <thead>
            <tr>
                <th><?= t('Name') ?></th>
                <th class="column-10"><?= t('Position') ?></th>
                <th class="column-10"><?= t('Active') ?></th>
                <th class="column-15"><?= t('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($departments as $dept): ?>
            <tr>
                <td><?= $this->text->e($dept['name']) ?></td>
                <td><?= (int)$dept['position'] ?></td>
                <td><?= $dept['is_active'] ? t('Yes') : t('No') ?></td>
                <td>
                    <?= $this->modal->medium(
                        'edit',
                        t('Edit'),
                        'DepartmentController',
                        'edit',
                        array('plugin' => 'AhvazTaskFields', 'project_id' => $project['id'], 'department_id' => $dept['id'])
                    ) ?>
                    |
                    <?= $this->modal->confirm(
                        'trash-o',
                        t('Remove'),
                        'DepartmentController',
                        'remove',
                        array('plugin' => 'AhvazTaskFields', 'project_id' => $project['id'], 'department_id' => $dept['id'])
                    ) ?>
                </td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
