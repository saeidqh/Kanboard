<?php
$canAnalytics = $this->hierarchicalRoleHelper->canAccessModule($project['id'], 'analytics');
if (!$canAnalytics):
?>
<div class="page-header">
    <h2><?= t('Access denied') ?></h2>
</div>
<p class="alert alert-error">
    <?= t('You do not have access to Analytics. Ask a project manager to grant the "Analytics / Charts" module to your role.') ?>
</p>
<?php else: ?>

<?php if ($is_ajax): ?>
    <div class="page-header">
        <h2><?= $this->text->e($title) ?></h2>
    </div>
<?php else: ?>
    <?= $this->projectHeader->render($project, 'TaskListController', 'show') ?>
<?php endif ?>
<section class="sidebar-container">
    <?= $this->render($sidebar_template, array('project' => $project)) ?>

    <div class="sidebar-content">
        <?= $content_for_sublayout ?>
    </div>
</section>
<?php endif ?>
