<div class="req-modal">
    <div class="req-modal-header">
        <h2><?= t('New Request') ?></h2>
        <p class="req-modal-sub"><?= t('Choose a project, pick a recipient, and send your request.') ?></p>
    </div>

    <form method="post"
          action="<?= $this->url->href('RequestController', 'save', array('plugin' => 'RequestPlugin')) ?>"
          class="req-form"
          autocomplete="off"
          id="req-create-form">
        <?= $this->form->csrf() ?>

        <div class="req-form-grid">
            <div class="req-field req-field-full">
                <label for="form-title"><?= t('Title') ?> <span class="req-required">*</span></label>
                <input type="text" name="title" id="form-title" required maxlength="255"
                       value="<?= isset($values['title']) ? $this->text->e($values['title']) : '' ?>"
                       placeholder="<?= t('Short summary of your request') ?>" autofocus>
                <?php if (!empty($errors['title'])): ?>
                    <div class="req-error"><?= $this->text->e($errors['title'][0]) ?></div>
                <?php endif ?>
            </div>

            <div class="req-field req-field-full">
                <label for="form-description"><?= t('Description') ?></label>
                <textarea name="description" id="form-description" rows="4"
                          placeholder="<?= t('Details, context, expected outcome…') ?>"><?= isset($values['description']) ? $this->text->e($values['description']) : '' ?></textarea>
            </div>

            <div class="req-field">
                <label for="form-project_id"><?= t('Project') ?> <span class="req-required">*</span></label>
                <select name="project_id" id="form-project_id" required class="req-project-select">
                    <option value="">— <?= t('Select a project') ?> —</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= (int) $p['id'] ?>"
                            <?= (!empty($values['project_id']) && (int)$values['project_id'] === (int)$p['id']) ? 'selected' : '' ?>>
                            <?= $this->text->e($p['name']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
                <?php if (!empty($errors['project_id'])): ?>
                    <div class="req-error"><?= $this->text->e($errors['project_id'][0]) ?></div>
                <?php endif ?>
            </div>

            <div class="req-field">
                <label for="form-assignee_id"><?= t('Send to') ?> <span class="req-required">*</span></label>
                <select name="assignee_id" id="form-assignee_id" required class="req-assignee-select"
                        data-users-url="<?= $this->url->href('RequestController', 'users', array('plugin' => 'RequestPlugin', 'project_id' => 999999)) ?>">
                    <option value="">— <?= t('Select project first') ?> —</option>
                </select>
                <div id="req-assignee-hint" class="req-hint"><?= t('Members of the selected project will appear here') ?></div>
                <?php if (!empty($errors['assignee_id'])): ?>
                    <div class="req-error"><?= $this->text->e($errors['assignee_id'][0]) ?></div>
                <?php endif ?>
            </div>

            <div class="req-field">
                <label for="form-category"><?= t('Category') ?></label>
                <select name="category" id="form-category">
                    <?php foreach ($categories as $key => $label): ?>
                        <option value="<?= $this->text->e($key) ?>"
                            <?= (!empty($values['category']) && $values['category'] === $key) ? 'selected' : '' ?>>
                            <?= $this->text->e($label) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="req-field">
                <label for="form-priority"><?= t('Priority') ?></label>
                <select name="priority" id="form-priority">
                    <?php foreach ($priorities as $key => $label): ?>
                        <option value="<?= (int) $key ?>"
                            <?= (isset($values['priority']) && (int)$values['priority'] === (int)$key) || (!isset($values['priority']) && (int)$key === 2) ? 'selected' : '' ?>>
                            <?= $this->text->e($label) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="req-field">
                <label for="form-due_date"><?= t('Due date') ?></label>
                <input type="date" name="due_date" id="form-due_date"
                       value="<?= isset($values['due_date']) ? $this->text->e($values['due_date']) : '' ?>">
            </div>

            <div class="req-field">
                <label for="form-needed_by"><?= t('Needed by') ?></label>
                <input type="date" name="needed_by" id="form-needed_by"
                       value="<?= isset($values['needed_by']) ? $this->text->e($values['needed_by']) : '' ?>">
            </div>
        </div>

        <div class="req-modal-actions">
            <a href="<?= $this->url->href('RequestController', 'inbox', array('plugin' => 'RequestPlugin')) ?>" class="btn req-btn-cancel js-modal-close">
                <?= t('Cancel') ?>
            </a>
            <button type="submit" class="btn btn-blue req-btn-submit">
                <i class="fa fa-paper-plane"></i> <?= t('Submit Request') ?>
            </button>
        </div>
    </form>
</div>

