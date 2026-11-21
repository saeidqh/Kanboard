<div class="page-header">
    <h2><?= t('Organizational Structure') ?></h2>
    <ul>
        <li>
            <?= $this->modal->medium(
                'plus',
                t('New Department'),
                'DepartmentController',
                'create',
                array('plugin' => 'AhvazTaskFields', 'project_id' => $project['id'])
            ) ?>
        </li>
        <li>
            <?= $this->url->link(
                t('Departments'),
                'DepartmentController',
                'index',
                array('plugin' => 'AhvazTaskFields', 'project_id' => $project['id'])
            ) ?>
        </li>
        <li>
            <?= $this->modal->medium(
                'plus',
                t('New Role'),
                'HierarchicalRoleController',
                'create',
                array('plugin' => 'HierarchicalRoles', 'project_id' => $project['id'])
            ) ?>
        </li>
        <li><?= $this->url->link(t('Assign Users'), 'HierarchicalRoleController', 'users', array('plugin' => 'HierarchicalRoles', 'project_id' => $project['id'])) ?></li>
        <li><?= $this->url->link(t('Module Access'), 'HierarchicalRoleController', 'access', array('plugin' => 'HierarchicalRoles', 'project_id' => $project['id'])) ?></li>
        <li><?= $this->url->link(t('Form Fields'), 'FormFieldController', 'index', array('plugin' => 'AhvazTaskFields', 'project_id' => $project['id'])) ?></li>
    </ul>
</div>

<?php if (empty($roles)): ?>
    <p class="alert"><?= t('No roles defined yet. Create the first role (usually Level 1 - top manager).') ?></p>
<?php else: ?>

<div class="panel">
    <h3><?= t('Role Tree') ?></h3>
    <?php
    function renderRoleTreeAhvaz(array $nodes, $view, $project, $depth = 0) {
        if (empty($nodes)) {
            return;
        }
        echo '<ul style="list-style:none; padding-left:'.($depth * 24).'px; margin:0;">';
        foreach ($nodes as $node) {
            echo '<li style="margin:6px 0; padding:8px 12px; background:#f8f9fa; border-radius:4px; border-left:4px solid #3498db;">';
            echo '<strong>'.e($node['title']).'</strong>';
            echo ' <small style="color:#888;">Level '.$node['level'].'</small>';
            echo ' &nbsp; ';
            echo $view->modal->medium(
                'edit',
                '',
                'HierarchicalRoleController',
                'edit',
                array('plugin' => 'HierarchicalRoles', 'project_id' => $project['id'], 'role_id' => $node['id'])
            );
            echo ' ';
            echo $view->url->link(
                '<i class="fa fa-trash fa-fw"></i>',
                'HierarchicalRoleController',
                'confirm',
                array('plugin' => 'HierarchicalRoles', 'project_id' => $project['id'], 'role_id' => $node['id']),
                false,
                'btn btn-xs'
            );
            if (!empty($node['description'])) {
                echo '<br><small style="color:#666;">'.e($node['description']).'</small>';
            }
            if (!empty($node['children'])) {
                renderRoleTreeAhvaz($node['children'], $view, $project, $depth + 1);
            }
            echo '</li>';
        }
        echo '</ul>';
    }
    renderRoleTreeAhvaz($tree, $this, $project);
    ?>
</div>

<table class="table-striped table-scrolling">
    <thead>
        <tr>
            <th><?= t('ID') ?></th>
            <th><?= t('Title') ?></th>
            <th><?= t('Level') ?></th>
            <th><?= t('Parent') ?></th>
            <th><?= t('Actions') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        $roleMap = array();
        foreach ($roles as $r) {
            $roleMap[$r['id']] = $r['title'];
        }
        foreach ($roles as $role):
        ?>
        <tr>
            <td><?= $role['id'] ?></td>
            <td><?= $this->text->e($role['title']) ?></td>
            <td><?= $role['level'] ?></td>
            <td><?= $role['parent_id'] ? $this->text->e(isset($roleMap[$role['parent_id']]) ? $roleMap[$role['parent_id']] : '-') : '—' ?></td>
            <td>
                <?= $this->modal->medium(
                    'edit',
                    t('Edit'),
                    'HierarchicalRoleController',
                    'edit',
                    array('plugin' => 'HierarchicalRoles', 'project_id' => $project['id'], 'role_id' => $role['id'])
                ) ?>
                |
                <?= $this->url->link(
                    t('Remove'),
                    'HierarchicalRoleController',
                    'confirm',
                    array('plugin' => 'HierarchicalRoles', 'project_id' => $project['id'], 'role_id' => $role['id'])
                ) ?>
            </td>
        </tr>
        <?php endforeach ?>
    </tbody>
</table>
<?php endif ?>
