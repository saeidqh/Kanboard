<div class="page-header">
    <h2><?= t('Form Fields Visibility') ?></h2>
    <ul>
        <li>
            <?= $this->url->link(
                '← ' . t('Back to Roles'),
                'HierarchicalRoleController',
                'index',
                array('plugin' => 'HierarchicalRoles', 'project_id' => $project['id'])
            ) ?>
        </li>
    </ul>
</div>

<p class="form-help">
    <?= t('Set 0 to hide a field, 1 to show it. Changes apply to the task creation form for this project.') ?>
</p>

<table class="table-striped table-scrolling">
    <thead>
        <tr>
            <th><?= t('Field') ?></th>
            <th><?= t('Key') ?></th>
            <th><?= t('Visible') ?></th>
            <th><?= t('Action') ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($fields as $field): ?>
        <tr>
            <td><strong><?= $this->text->e($field['label']) ?></strong></td>
            <td><code><?= $this->text->e($field['field_key']) ?></code></td>
            <td>
                <?php if ((int)$field['is_visible'] === 1): ?>
                    <span style="color:green;font-weight:bold;">1 — <?= t('Shown') ?></span>
                <?php else: ?>
                    <span style="color:#c00;font-weight:bold;">0 — <?= t('Hidden') ?></span>
                <?php endif ?>
            </td>
            <td>
                <?php if ((int)$field['is_visible'] === 1): ?>
                    <?= $this->url->link(
                        t('Hide (set 0)'),
                        'FormFieldController',
                        'toggle',
                        array(
                            'plugin'     => 'AhvazTaskFields',
                            'project_id' => $project['id'],
                            'form_key'   => $form_key,
                            'field_key'  => $field['field_key'],
                            'visible'    => 0,
                        ),
                        true,
                        'btn btn-sm'
                    ) ?>
                <?php else: ?>
                    <?= $this->url->link(
                        t('Show (set 1)'),
                        'FormFieldController',
                        'toggle',
                        array(
                            'plugin'     => 'AhvazTaskFields',
                            'project_id' => $project['id'],
                            'form_key'   => $form_key,
                            'field_key'  => $field['field_key'],
                            'visible'    => 1,
                        ),
                        true,
                        'btn btn-sm'
                    ) ?>
                <?php endif ?>
            </td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>
