<?php
$rq = $request;
$statusLabel = isset($statuses[$rq['status']]) ? $statuses[$rq['status']] : $rq['status'];
$priorityLabel = isset($priorities[$rq['priority']]) ? $priorities[$rq['priority']] : $rq['priority'];
$categoryLabel = isset($categories[$rq['category']]) ? $categories[$rq['category']] : $rq['category'];
?>
<div class="page-header">
    <h2>
        <span class="req-ref"><?= $this->text->e($rq['reference']) ?></span>
        <?= $this->text->e($rq['title']) ?>
        <?php if (!empty($rq['linked_task_id'])): ?>
            <span class="req-badge req-badge-task"><?= t('Converted to Task') ?> #<?= (int) $rq['linked_task_id'] ?></span>
        <?php endif ?>
    </h2>
    <ul>
        <li><?= $this->url->link(t('Back to list'), 'RequestController', 'inbox', array('plugin' => 'RequestPlugin')) ?></li>
    </ul>
</div>

<div class="req-show-grid">
    <div class="req-show-main">
        <div class="panel">
            <div class="req-meta">
                <span class="req-status req-status-<?= $this->text->e($rq['status']) ?>"><?= $this->text->e($statusLabel) ?></span>
                <span class="req-meta-item"><strong><?= t('Priority') ?>:</strong> <?= $this->text->e($priorityLabel) ?></span>
                <span class="req-meta-item"><strong><?= t('Category') ?>:</strong> <?= $this->text->e($categoryLabel) ?></span>
                <?php if (!empty($rq['project'])): ?>
                    <span class="req-meta-item"><strong><?= t('Project') ?>:</strong> <?= $this->text->e($rq['project']['name']) ?></span>
                <?php endif ?>
            </div>

            <div class="req-people">
                <div><strong><?= t('Requester') ?>:</strong> <?= $this->text->e($rq['requester']['name'] ?: $rq['requester']['username']) ?></div>
                <div><strong><?= t('Assignee') ?>:</strong> <?= $this->text->e($rq['assignee']['name'] ?: $rq['assignee']['username']) ?></div>
                <div><strong><?= t('Current holder') ?>:</strong> <?= $this->text->e($rq['holder']['name'] ?: $rq['holder']['username']) ?></div>
            </div>

            <h3><?= t('Description') ?></h3>
            <div class="markdown">
                <?= $this->text->markdown($rq['description']) ?>
            </div>

            <?php if (!empty($rq['acceptance_note'])): ?>
                <div class="alert alert-success">
                    <strong><?= t('Acceptance note') ?>:</strong>
                    <?= $this->text->e($rq['acceptance_note']) ?>
                </div>
            <?php endif ?>

            <?php if (!empty($rq['rejection_reason'])): ?>
                <div class="alert alert-error">
                    <strong><?= t('Rejection reason') ?>:</strong>
                    <?= $this->text->e($rq['rejection_reason']) ?>
                </div>
            <?php endif ?>

            <?php if (!empty($rq['linked_task_id']) && !empty($rq['task'])): ?>
                <div class="alert">
                    <strong><?= t('Linked task') ?>:</strong>
                    <?= $this->url->link(
                        '#' . $rq['linked_task_id'] . ' — ' . $rq['task']['title'],
                        'TaskViewController',
                        'show',
                        array('task_id' => $rq['linked_task_id'], 'project_id' => $rq['project_id'])
                    ) ?>
                    <?php if (!empty($rq['converter'])): ?>
                        <br><small><?= t('Converted by') ?> <?= $this->text->e($rq['converter']['name'] ?: $rq['converter']['username']) ?>
                        — <?= $this->dt->datetime(!empty($rq['converted_at']) ? strtotime($rq['converted_at']) : 0) ?></small>
                    <?php endif ?>
                </div>
            <?php endif ?>
        </div>

        <!-- Actions -->
        <?php if ($can_act || $can_convert || $can_forward || $is_requester || $is_holder): ?>
        <div class="panel req-actions">
            <h3><?= t('Actions') ?></h3>
            <div class="req-action-buttons">
                <?php if ($can_act && in_array($rq['status'], array('submitted', 'forwarded', 'under_review'), true)): ?>
                    <form method="post" action="<?= $this->url->href('RequestController', 'accept', array('plugin' => 'RequestPlugin', 'request_id' => $rq['id'])) ?>" class="req-inline-form">
                        <?= $this->form->csrf() ?>
                        <input type="text" name="note" placeholder="<?= t('Optional acceptance note') ?>" class="form-input-small">
                        <button type="submit" class="btn btn-blue"><?= t('Accept') ?></button>
                    </form>
                    <form method="post" action="<?= $this->url->href('RequestController', 'reject', array('plugin' => 'RequestPlugin', 'request_id' => $rq['id'])) ?>" class="req-inline-form">
                        <?= $this->form->csrf() ?>
                        <input type="text" name="reason" placeholder="<?= t('Rejection reason') ?>" class="form-input-small" required>
                        <button type="submit" class="btn btn-red"><?= t('Reject') ?></button>
                    </form>
                <?php endif ?>

                <?php if ($can_forward): ?>
                    <form method="post" action="<?= $this->url->href('RequestController', 'forward', array('plugin' => 'RequestPlugin', 'request_id' => $rq['id'])) ?>" class="req-inline-form">
                        <?= $this->form->csrf() ?>
                        <select name="to_user_id" required>
                            <option value="">— <?= t('Forward to…') ?> —</option>
                            <?php foreach ($project_members as $uid => $uname): ?>
                                <?php if ((int)$uid !== (int)$this->userSession->getId()): ?>
                                    <option value="<?= (int) $uid ?>"><?= $this->text->e($uname) ?></option>
                                <?php endif ?>
                            <?php endforeach ?>
                        </select>
                        <input type="text" name="note" placeholder="<?= t('Note (optional)') ?>" class="form-input-small">
                        <button type="submit" class="btn"><?= t('Forward') ?></button>
                    </form>
                <?php endif ?>

                <?php if ($can_convert): ?>
                    <form method="post" action="<?= $this->url->href('RequestController', 'convert', array('plugin' => 'RequestPlugin', 'request_id' => $rq['id'])) ?>" class="req-inline-form" onsubmit="return confirm('<?= t('Convert this request to a task? This can only be done once.') ?>');">
                        <?= $this->form->csrf() ?>
                        <button type="submit" class="btn btn-blue"><?= t('Convert to Task') ?></button>
                    </form>
                <?php endif ?>

                <?php if (($is_holder || $is_requester) && !in_array($rq['status'], array('completed', 'cancelled', 'rejected'), true)): ?>
                    <form method="post" action="<?= $this->url->href('RequestController', 'complete', array('plugin' => 'RequestPlugin', 'request_id' => $rq['id'])) ?>" class="req-inline-form">
                        <?= $this->form->csrf() ?>
                        <input type="text" name="note" placeholder="<?= t('Completion note') ?>" class="form-input-small">
                        <button type="submit" class="btn"><?= t('Mark Completed') ?></button>
                    </form>
                <?php endif ?>
            </div>
        </div>
        <?php endif ?>

        <!-- Comments -->
        <div class="panel">
            <h3><?= t('Discussion') ?></h3>
            <?php if (empty($comments)): ?>
                <p class="form-help"><?= t('No comments yet.') ?></p>
            <?php else: ?>
                <ul class="req-comments">
                    <?php foreach ($comments as $c): ?>
                        <li>
                            <div class="req-comment-head">
                                <strong><?= $this->text->e($c['user_name']) ?></strong>
                                <small><?= $this->dt->datetime(!empty($c['created_at']) ? strtotime($c['created_at']) : 0) ?></small>
                            </div>
                            <div class="req-comment-body"><?= nl2br($this->text->e($c['comment'])) ?></div>
                        </li>
                    <?php endforeach ?>
                </ul>
            <?php endif ?>

            <form method="post" action="<?= $this->url->href('RequestController', 'comment', array('plugin' => 'RequestPlugin', 'request_id' => $rq['id'])) ?>" class="req-comment-form">
                <?= $this->form->csrf() ?>
                <textarea name="comment" rows="3" required placeholder="<?= t('Write a reply…') ?>"></textarea>
                <div class="form-actions">
                    <button type="submit" class="btn btn-blue"><?= t('Add comment') ?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="req-show-side">
        <div class="panel">
            <h3><?= t('Timeline') ?></h3>
            <?php if (empty($history)): ?>
                <p class="form-help"><?= t('No history yet.') ?></p>
            <?php else: ?>
                <ul class="req-timeline">
                    <?php foreach ($history as $h): ?>
                        <li class="req-timeline-item">
                            <div class="req-timeline-action">
                                <strong><?= $this->text->e(ucfirst(str_replace('_', ' ', $h['action']))) ?></strong>
                                <small><?= $this->dt->datetime(!empty($h['created_at']) ? strtotime($h['created_at']) : 0) ?></small>
                            </div>
                            <div class="req-timeline-who">
                                <?= $this->text->e($h['user_name']) ?>
                                <?php if (!empty($h['to_user_name'])): ?>
                                    → <?= $this->text->e($h['to_user_name']) ?>
                                <?php endif ?>
                            </div>
                            <?php if (!empty($h['note'])): ?>
                                <div class="req-timeline-note"><?= $this->text->e($h['note']) ?></div>
                            <?php endif ?>
                        </li>
                    <?php endforeach ?>
                </ul>
            <?php endif ?>
        </div>

        <div class="panel">
            <h3><?= t('Dates') ?></h3>
            <ul class="no-bullet">
                <li><strong><?= t('Created') ?>:</strong> <?= $this->dt->datetime(!empty($rq['created_at']) ? strtotime($rq['created_at']) : 0) ?></li>
                <li><strong><?= t('Updated') ?>:</strong> <?= $this->dt->datetime(!empty($rq['updated_at']) ? strtotime($rq['updated_at']) : 0) ?></li>
                <?php if (!empty($rq['due_date'])): ?>
                    <li><strong><?= t('Due date') ?>:</strong> <?= $this->dt->date(!empty($rq['due_date']) ? strtotime($rq['due_date']) : 0) ?></li>
                <?php endif ?>
                <?php if (!empty($rq['needed_by'])): ?>
                    <li><strong><?= t('Needed by') ?>:</strong> <?= $this->dt->date(!empty($rq['needed_by']) ? strtotime($rq['needed_by']) : 0) ?></li>
                <?php endif ?>
                <?php if (!empty($rq['closed_at'])): ?>
                    <li><strong><?= t('Closed') ?>:</strong> <?= $this->dt->datetime(!empty($rq['closed_at']) ? strtotime($rq['closed_at']) : 0) ?></li>
                <?php endif ?>
            </ul>
        </div>
    </div>
</div>
