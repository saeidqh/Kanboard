<?php
$taskId = isset($task['id']) ? (int) $task['id'] : 0;
$projectId = isset($task['project_id']) ? (int) $task['project_id'] : 0;

$raw = null;
$needs = '0';
if ($taskId > 0) {
    $raw = $this->task->taskMetadataModel->get($taskId, 'manual_progress', null);
    $needs = $this->task->taskMetadataModel->get($taskId, 'needs_progress_prompt', '0');
}

$hasValue = ($raw !== null && $raw !== '');
$pct = $hasValue ? max(0, min(100, (float) $raw)) : 0;
$needsPrompt = ((string) $needs === '1');
$title = $needsPrompt
    ? t('Enter progress percentage')
    : (t('Progress') . ': ' . ($hasValue ? ((int) $pct) . '%' : '—'));
?>
<div class="mp-bar-wrap<?= $needsPrompt ? ' mp-needs-prompt' : '' ?>"
     data-task-id="<?= $taskId ?>"
     data-project-id="<?= $projectId ?>"
     data-progress="<?= $hasValue ? (int) $pct : '' ?>"
     data-needs-prompt="<?= $needsPrompt ? '1' : '0' ?>"
     title="<?= $this->text->e($title) ?>">
    <div class="mp-bar-track">
        <div class="mp-bar-fill" style="width: <?= $hasValue ? $pct : 0 ?>%;"></div>
    </div>
    <div class="mp-bar-label">
        <?php if ($needsPrompt): ?>
            <span class="mp-bar-ask"><?= t('Set percent') ?></span>
        <?php elseif ($hasValue): ?>
            <span><?= (int) $pct ?>%</span>
        <?php else: ?>
            <span class="mp-bar-empty">0%</span>
        <?php endif ?>
    </div>
</div>
