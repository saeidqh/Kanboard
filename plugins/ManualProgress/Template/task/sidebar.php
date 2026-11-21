<?php
$taskId = (int) $task['id'];
$raw = $this->task->taskMetadataModel->get($taskId, 'manual_progress', '');
$needs = $this->task->taskMetadataModel->get($taskId, 'needs_progress_prompt', '0');
$value = ($raw === null || $raw === '') ? '' : (int) $raw;
?>
<li class="mp-sidebar-item">
    <strong><?= t('Progress') ?></strong>
    <div class="mp-sidebar-form-wrap">
        <form method="post"
              action="<?= $this->url->href('ManualProgressController', 'save', array('plugin' => 'ManualProgress', 'task_id' => $task['id'], 'project_id' => $task['project_id'])) ?>"
              class="mp-sidebar-form">
            <?= $this->form->csrf() ?>
            <div class="mp-input-row">
                <input type="number"
                       name="manual_progress"
                       min="0"
                       max="100"
                       step="1"
                       value="<?= $value !== '' ? $value : '' ?>"
                       placeholder="0-100"
                       class="mp-input"
                       required>
                <span class="mp-suffix">%</span>
                <button type="submit" class="btn btn-blue btn-sm"><?= t('Save') ?></button>
            </div>
            <?php if ((string) $needs === '1'): ?>
                <p class="mp-hint mp-hint-warn"><?= t('This task left Done — enter the current progress percentage.') ?></p>
            <?php else: ?>
                <p class="mp-hint"><?= t('Self-reported progress. Moving to Done sets 100%%.') ?></p>
            <?php endif ?>
        </form>
    </div>
</li>
