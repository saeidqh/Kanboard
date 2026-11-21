<div class="mp-prompt">
    <h2><?= t('Progress percentage') ?></h2>
    <p><?= t('This task was moved out of Done. Enter the progress percentage that should be shown on the board.') ?></p>
    <form method="post"
          action="<?= $this->url->href('ManualProgressController', 'save', array('plugin' => 'ManualProgress', 'task_id' => $task['id'], 'project_id' => $task['project_id'])) ?>"
          class="mp-prompt-form"
          id="mp-prompt-form">
        <?= $this->form->csrf() ?>
        <label for="mp-prompt-input"><?= t('Progress') ?> (0–100)</label>
        <div class="mp-input-row">
            <input type="number" id="mp-prompt-input" name="manual_progress" min="0" max="100" step="1" value="90" required autofocus class="mp-input">
            <span class="mp-suffix">%</span>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-blue"><?= t('Save') ?></button>
        </div>
    </form>
</div>
