<?php if ($this->user->hasProjectAccess('ProjectEditController', 'show', $project['id'])): ?>
    <li <?= $this->app->checkMenuSelection('DepartmentController') ?>>
        <?= $this->url->link(
            t('Departments'),
            'DepartmentController',
            'index',
            ['plugin' => 'AhvazTaskFields', 'project_id' => $project['id']]
        ) ?>
    </li>
<?php endif ?>
