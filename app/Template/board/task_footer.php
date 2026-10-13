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
// محاسبه ترکیبی پیشرفت: اولویت با زمان (Time Tracking) است
$progress_percent = 0;
$progress_label = '';

$time_estimated = ! empty($task['time_estimated']) ? (float)$task['time_estimated'] : 0;
$time_spent     = ! empty($task['time_spent']) ? (float)$task['time_spent'] : 0;

if ($time_estimated > 0) {
    // ۱. اگر زمان تخمینی وجود داشت: محاسبه بر اساس ساعت کارکرد
    $progress_percent = round(($time_spent / $time_estimated) * 100);
    if ($progress_percent > 100) {
        $progress_percent = 100; // سقف نمایش ۱۰۰ درصد
    }
    $progress_label = $time_spent . 'h / ' . $time_estimated . 'h (' . $progress_percent . '%)';
} elseif (! empty($task['nb_subtasks']) && $task['nb_subtasks'] > 0) {
    // ۲. اگر زمان نبود: محاسبه بر اساس ساب‌تسک‌های انجام‌شده
    $progress_percent = round(($task['nb_completed_subtasks'] / $task['nb_subtasks']) * 100);
    $progress_label = $task['nb_completed_subtasks'] . '/' . $task['nb_subtasks'] . ' ساب‌تسک (' . $progress_percent . '%)';
}
?>

<?php if ($progress_label !== ''): ?>
<div style="margin-top: 6px; padding: 2px 0;">
    <div style="display: flex; justify-content: space-between; font-size: 11px; color: #555; margin-bottom: 3px; font-weight: bold; direction: ltr;">
        <span><?= $progress_label ?></span>
    </div>
    <div style="background-color: #e0e0e0; border-radius: 4px; height: 7px; width: 100%; overflow: hidden;">
        <div style="background-color: #27ae60; height: 100%; width: <?= $progress_percent ?>%; transition: width 0.3s ease;"></div>
    </div>
</div>
<?php endif ?>

<div class="task-board-icons">
    <div class="task-board-icons-row">
        <?= $this->hook->render('template:board:task:icons', array('task' => $task)) ?>
    </div>
</div>
