<div class="page-header">
    <h2><?= $this->text->e($title) ?></h2>
    <ul>
        <li>
            <?= $this->modal->large(
                'plus',
                t('New Request'),
                'RequestController',
                'create',
                array('plugin' => 'RequestPlugin')
            ) ?>
        </li>
    </ul>
</div>

<div class="req-tabs margin-bottom">
    <ul class="req-tab-list">
        <li class="<?= $tab === 'inbox' ? 'active' : '' ?>">
            <?= $this->url->link(t('Inbox'), 'RequestController', 'inbox', array('plugin' => 'RequestPlugin')) ?>
        </li>
        <li class="<?= $tab === 'sent' ? 'active' : '' ?>">
            <?= $this->url->link(t('Sent'), 'RequestController', 'sent', array('plugin' => 'RequestPlugin')) ?>
        </li>
        <li class="<?= $tab === 'all' ? 'active' : '' ?>">
            <?= $this->url->link(t('All'), 'RequestController', 'all', array('plugin' => 'RequestPlugin')) ?>
        </li>
    </ul>
</div>

<?php if (empty($requests)): ?>
    <p class="alert"><?= t('No requests found.') ?></p>
<?php else: ?>
<table class="table-striped table-scrolling">
    <thead>
        <tr>
            <th><?= t('Reference') ?></th>
            <th><?= t('Title') ?></th>
            <th><?= t('Project') ?></th>
            <th><?= t('From') ?></th>
            <th><?= t('Holder') ?></th>
            <th><?= t('Status') ?></th>
            <th><?= t('Priority') ?></th>
            <th><?= t('Updated') ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($requests as $r): ?>
        <tr>
            <td>
                <strong><?= $this->text->e($r['reference']) ?></strong>
                <?php if (!empty($r['is_converted'])): ?>
                    <span class="req-badge req-badge-task" title="<?= t('Converted to task') ?>">Task</span>
                <?php endif ?>
            </td>
            <td>
                <?= $this->url->link(
                    $this->text->e($r['title']),
                    'RequestController',
                    'show',
                    array('plugin' => 'RequestPlugin', 'request_id' => $r['id'])
                ) ?>
            </td>
            <td><?= $this->text->e($r['project_name']) ?></td>
            <td><?= $this->text->e($r['requester_name']) ?></td>
            <td><?= $this->text->e($r['holder_name']) ?></td>
            <td><span class="req-status req-status-<?= $this->text->e($r['status']) ?>"><?= $this->text->e($r['status_label']) ?></span></td>
            <td><?= $this->text->e($r['priority_label']) ?></td>
            <td><?= $this->dt->datetime(!empty($r['updated_at']) ? strtotime($r['updated_at']) : 0) ?></td>
            <td>
                <?= $this->url->link(
                    t('Open'),
                    'RequestController',
                    'show',
                    array('plugin' => 'RequestPlugin', 'request_id' => $r['id']),
                    false,
                    'btn btn-blue btn-sm'
                ) ?>
            </td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>
<?php endif ?>
