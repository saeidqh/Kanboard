<div class="page-header">
    <h2><?= t('Remove department') ?></h2>
</div>

<div class="confirm">
    <p class="alert alert-info">
        <?= t('Do you really want to remove this department: "%s"?', $this->text->e($department['name'])) ?>
    </p>

    <div class="form-actions">
        <?= $this->url->link(
            t('Yes'),
            'DepartmentController',
            'remove',
            array(
                'plugin'        => 'AhvazTaskFields',
                'project_id'    => $project['id'],
                'department_id' => $department['id'],
            ),
            true,   /* CSRF */
            'btn btn-red'
        ) ?>
        <?= $this->url->link(
            t('No'),
            'DepartmentController',
            'index',
            array(
                'plugin'     => 'AhvazTaskFields',
                'project_id' => $project['id'],
            ),
            false,
            'btn'
        ) ?>
    </div>
</div>
