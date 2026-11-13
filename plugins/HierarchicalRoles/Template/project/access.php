<div class="page-header">
    <h2><?= t('Module Access Control') ?></h2>
    <ul>
        <li><?= $this->url->link(t('Back to Roles'), 'HierarchicalRoleController', 'index', array('plugin' => 'HierarchicalRoles', 'project_id' => $project['id'])) ?></li>
    </ul>
</div>

<p class="alert alert-info">
    <?= t('Select which roles are allowed to access each module. Users without access will not see the related menus and will get an error if they open the page directly.') ?>
</p>

<?php if (empty($roles)): ?>
    <p class="alert"><?= t('Please create roles first.') ?></p>
<?php elseif (empty($modules)): ?>
    <p class="alert"><?= t('No modules defined. Run the SQL seed for plugin_access_modules.') ?></p>
<?php else: ?>

<form method="post" action="<?= $this->url->href('HierarchicalRoleController', 'saveAccess', array('plugin' => 'HierarchicalRoles', 'project_id' => $project['id'])) ?>">
    <?= $this->form->csrf() ?>

    <div class="table-list" style="overflow-x:auto;">
    <table class="table-striped table-scrolling">
        <thead>
            <tr>
                <th style="min-width:160px;"><?= t('Role') ?></th>
                <th><?= t('Level') ?></th>
                <?php foreach ($modules as $module): ?>
                    <th style="text-align:center; min-width:100px;" title="<?= $this->text->e($module['description']) ?>">
                        <?= $this->text->e($module['name']) ?>
                        <br><small style="font-weight:normal; color:#888;"><?= $this->text->e($module['slug']) ?></small>
                    </th>
                <?php endforeach ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($roles as $role): ?>
            <tr>
                <td><strong><?= $this->text->e($role['title']) ?></strong></td>
                <td><?= $role['level'] ?></td>
                <?php foreach ($modules as $module):
                    $checked = isset($access_map[$role['id']]) && in_array($module['id'], $access_map[$role['id']]);
                ?>
                <td style="text-align:center;">
                    <input type="checkbox"
                           name="access[<?= $role['id'] ?>][]"
                           value="<?= $module['id'] ?>"
                           <?= $checked ? 'checked' : '' ?>>
                </td>
                <?php endforeach ?>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-blue"><?= t('Save Access') ?></button>
    </div>
</form>
<?php endif ?>
