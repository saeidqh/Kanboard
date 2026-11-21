<?php
$taskId = (int) $task['id'];
$raw = $this->task->taskMetadataModel->get($taskId, 'manual_progress', null);
$needs = $this->task->taskMetadataModel->get($taskId, 'needs_progress_prompt', '0');
$hasValue = ($raw !== null && $raw !== '');
$pct = $hasValue ? max(0, min(100, (float) $raw)) : 0;
$needsPrompt = ((string) $needs === '1');
?>
<li class="mp-details-bar-item">
    <strong><?= t('Progress') ?></strong>
    <span class="metadata">
        <div class="mp-bar-wrap<?= $needsPrompt ? ' mp-needs-prompt' : '' ?>"
             data-task-id="<?= $taskId ?>"
             data-project-id="<?= (int) $task['project_id'] ?>"
             data-progress="<?= $hasValue ? (int) $pct : '' ?>"
             style="min-width:160px; margin-top:4px;">
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
    </span>
</li>
