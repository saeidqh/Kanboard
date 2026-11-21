<?php if (! empty($task['category_id'])): ?>
<div class="task-board-category-container task-board-category-container-color">
    <span class="task-board-category category-<?= $this->text->e($task['category_name']) ?> <?= $task['category_color_id'] ? "color-{$task['category_color_id']}" : '' ?>">
        <?php if (! empty($not_editable)): ?>
            <?= $this->text->e($task['category_name']) ?>
        <?php else: ?>
            <?= $this->url->link(
                $this->text->e($task['category_name']),
                'TaskModificationController',
                'edit',
                array('task_id' => $task['id'], 'project_id' => $task['project_id']),
                false,
                'js-modal-medium'
            ) ?>
        <?php endif ?>
    </span>
</div>
<?php endif ?>

<?php if (! empty($task['category_id'])): ?>
<div class="task-board-category-container task-board-category-container-color">
    <span class="task-board-category category-<?= $this->text->e($task['category_name']) ?> <?= $task['category_color_id'] ? "color-{$task['category_color_id']}" : '' ?>">
        <?php if (! empty($not_editable)): ?>
            <?= $this->text->e($task['category_name']) ?>
        <?php else: ?>
            <?= $this->url->link(
                $this->text->e($task['category_name']),
                'TaskModificationController',
                'edit',
                array('task_id' => $task['id'], 'project_id' => $task['project_id']),
                false,
                'js-modal-medium'
            ) ?>
        <?php endif ?>
    </span>
</div>
<?php endif ?>

<?php
// پیشرفت دستی از metadata پلاگین ManualProgress
$raw = $this->task->taskMetadataModel->get($task['id'], 'manual_progress', null);
$hasValue = ($raw !== null && $raw !== '');
$progress_percent = $hasValue ? max(0, min(100, (int) $raw)) : 0;
$progress_label = $hasValue ? ($progress_percent . '%') : '';
?>

<?php if ($progress_label !== ''): ?>
<div class="mp-bar-wrap"
     data-task-id="<?= (int) $task['id'] ?>"
     data-project-id="<?= (int) $task['project_id'] ?>"
     data-progress="<?= (int) $progress_percent ?>"
     style="margin-top: 6px; padding: 2px 0;">
    <div style="display: flex; justify-content: space-between; font-size: 11px; color: #1e7e45; margin-bottom: 3px; font-weight: bold; direction: ltr;">
        <span><?= $progress_label ?></span>
    </div>
    <div style="background-color: #e8f8ef; border-radius: 4px; height: 7px; width: 100%; overflow: hidden;">
        <div style="background-color: #27ae60; height: 100%; width: <?= (int) $progress_percent ?>%; transition: width 0.3s ease;"></div>
    </div>
</div>
<?php endif ?>

<div class="task-board-icons">
    <div class="task-board-icons-row">
        <?= $this->hook->render('template:board:task:icons', array('task' => $task)) ?>
    </div>
</div>

<div class="task-board-icons">
    <div class="task-board-icons-row">
        <?= $this->hook->render('template:board:task:icons', array('task' => $task)) ?>
    </div>
</div>
