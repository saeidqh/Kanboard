<div class="page-header">
    <h2><?= t('Assign Users to Roles') ?></h2>
    <ul>
        <li><?= $this->url->link(t('Back to Roles'), 'HierarchicalRoleController', 'index', array('plugin' => 'HierarchicalRoles', 'project_id' => $project['id'])) ?></li>
    </ul>
</div>

<?php if (empty($roles)): ?>
    <p class="alert"><?= t('Please create roles first.') ?></p>
<?php elseif (empty($project_users)): ?>
    <p class="alert"><?= t('No users in this project.') ?></p>
<?php else: ?>

<form method="post" action="<?= $this->url->href('HierarchicalRoleController', 'saveUsers', array('plugin' => 'HierarchicalRoles', 'project_id' => $project['id'])) ?>">
    <?= $this->form->csrf() ?>

    <table class="table-striped table-scrolling">
        <thead>
            <tr>
                <th><?= t('User') ?></th>
                <th><?= t('Role') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $roleOptions = array('' => t('— No role —'));
            foreach ($roles as $role) {
                $prefix = str_repeat('— ', max(0, (int) $role['level'] - 1));
                $roleOptions[$role['id']] = $prefix . $role['title'] . ' (L' . $role['level'] . ')';
            }

            foreach ($project_users as $user):
                // Support both array rows and ensure id exists
                if (!is_array($user) || !isset($user['id'])) {
                    continue;
                }
                $uid = (int) $user['id'];
                $currentRole = isset($assignment_map[$uid]) ? $assignment_map[$uid] : '';
                $displayName = !empty($user['name']) ? $user['name'] : (isset($user['username']) ? $user['username'] : 'User #'.$uid);
                $username = isset($user['username']) ? $user['username'] : '';
            ?>
            <tr>
                <td>
                    <?= $this->text->e($displayName) ?>
                    <?php if ($username !== ''): ?>
                        <small style="color:#888;">(<?= $this->text->e($username) ?>)</small>
                    <?php endif ?>
                </td>
                <td>
                    <select name="assignments[<?= $uid ?>]" class="form-control">
                        <?php foreach ($roleOptions as $rid => $label): ?>
                            <option value="<?= $this->text->e($rid) ?>" <?= (string) $currentRole === (string) $rid ? 'selected' : '' ?>><?= $this->text->e($label) ?></option>
                        <?php endforeach ?>
                    </select>
                </td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>

    <div class="form-actions">
        <button type="submit" class="btn btn-blue"><?= t('Save Assignments') ?></button>
    </div>
</form>
<?php endif ?>
