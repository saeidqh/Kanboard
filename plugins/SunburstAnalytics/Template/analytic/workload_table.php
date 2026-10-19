<div class="workload-panel">
    <div class="panel-header">
        <h3><i class="fa fa-users"></i> <?= t('User Workload Distribution') ?></h3>
    </div>

    <?php if (empty($workloadData['users'])): ?>
        <p class="alert alert-info"><?= t('No active tasks found in this project.') ?></p>
    <?php else: ?>
        <?php
            // -------------------------------------------------------------
            // Step 1: Calculate team-wide maximum tasks per active column
            // (Exclude 'Done' because completed tasks are not active workload)
            // -------------------------------------------------------------
            $columnMaxUserTasks = [];
            foreach ($workloadData['users'] as $u) {
                if (!empty($u['columns'])) {
                    foreach ($u['columns'] as $colName => $colCount) {
                        $normalizedCol = strtolower(trim($colName));
                        if ($normalizedCol === 'done') {
                            continue; // Skip Done column
                        }
                        if (!isset($columnMaxUserTasks[$colName]) || $colCount > $columnMaxUserTasks[$colName]) {
                            $columnMaxUserTasks[$colName] = (int)$colCount;
                        }
                    }
                }
            }
        ?>

        <table class="table-striped table-scrolling workload-table">
            <thead>
                <tr>
                    <th style="width: 45px; text-align: center;">#</th>
                    <th><?= t('User') ?></th>
                    <th style="text-align: center; width: 140px;"><?= t('Open Tasks') ?></th>
                    <th style="width: 250px;"><?= t('Share') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($workloadData['users'] as $stats): ?>
                    <?php
                        $isHighest = !empty($stats['is_highest']);
                        $rowClass  = $isHighest ? 'workload-row-highest' : '';
                        $barClass  = $isHighest ? 'progress-bar-fill-highest' : 'progress-bar-fill';
                        $uid       = $stats['user_id'];
                    ?>
                    <tr class="<?= $rowClass ?>">
                        <td style="text-align: center;">
                           <button type="button" class="btn-toggle-columns" data-target="user-cols-<?= $uid ?>" title="Toggle task breakdown">
                                +
                            </button>
                        </td>
                        <td>
                            <strong><?= $this->text->e($stats['user_name']) ?></strong>
                            <?php if ($isHighest): ?>
                                <span class="workload-badge-highest">Max WIP</span>
                            <?php endif ?>
                        </td>
                        <td style="text-align: center; font-weight: bold; font-size: 1.1em;">
                            <?= (int)$stats['open_tasks_count'] ?> <?= t('tasks') ?>
                        </td>
                        <td>
                            <div class="progress-bar-container">
                                <div class="progress-bar-bg">
                                    <div class="<?= $barClass ?>" style="width: <?= min(100, $stats['task_percentage']) ?>%;"></div>
                                </div>
                                <span class="progress-bar-text"><?= $stats['task_percentage'] ?>%</span>
                            </div>
                        </td>
                    </tr>

                    <!-- Expandable column-breakdown row -->
                    <tr id="user-cols-<?= $uid ?>" class="user-columns-detail-row" style="display: none;">
                        <td colspan="4">
                            <div class="user-columns-box">
                                <div class="user-columns-title">
                                    <i class="fa fa-columns"></i> Column breakdown for
                                    <strong><?= $this->text->e($stats['user_name']) ?></strong>:
                                </div>
                                <div class="columns-badge-list">
                                    <?php if (!empty($stats['columns'])): ?>
                                        <?php foreach ($stats['columns'] as $colName => $colCount): ?>
                                            <?php
                                                $normalizedCol = strtolower(trim($colName));
                                                $isDone = ($normalizedCol === 'done');
                                                
                                                // Highest load across the whole project for this specific column:
                                                // Only awarded if the user holds the maximum count in that column across all team members,
                                                // the count is greater than 1 (avoiding trivial single tasks), and it is not the Done column.
                                                $colMaxAcrossTeam = isset($columnMaxUserTasks[$colName]) ? $columnMaxUserTasks[$colName] : 0;
                                                $isBottleneck = (!$isDone && $colCount > 1 && $colCount === $colMaxAcrossTeam);

                                                $badgeClass = $isBottleneck ? 'badge-column-max' : ($isDone ? 'badge-column-done' : 'badge-column-normal');
                                            ?>
                                            <div class="column-stat-pill <?= $badgeClass ?>">
                                                <span class="pill-title"><?= $this->text->e($colName) ?></span>
                                                <span class="pill-count"><?= $colCount ?> <?= $colCount === 1 ? 'task' : 'tasks' ?></span>
                                                <?php if ($isBottleneck): ?>
                                                    <span class="pill-tag">Highest load</span>
                                                <?php endif ?>
                                            </div>
                                        <?php endforeach ?>
                                    <?php else: ?>
                                        <span class="text-muted">No column records found.</span>
                                    <?php endif ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>

        <!-- Critical Delay Alerts -->
        <?php
            $hasDelays = false;
            foreach ($workloadData['users'] as $u) {
                if (!empty($u['delayed_tasks'])) {
                    $hasDelays = true;
                    break;
                }
            }
        ?>
        <?php if ($hasDelays): ?>
            <div class="delay-alerts-section">
                <h4><i class="fa fa-exclamation-triangle"></i> <?= t('Critical Delay Alerts (Working Columns)') ?></h4>
                <?php foreach ($workloadData['users'] as $stats): ?>
                    <?php if (!empty($stats['delayed_tasks'])): ?>
                        <?php foreach ($stats['delayed_tasks'] as $dt): ?>
                            <div class="delay-alert-item">
                                <div class="delay-alert-header">
                                    <strong><?= $this->text->e($stats['user_name']) ?></strong>
                                    &larr;
                                    <a href="?controller=TaskViewController&action=show&task_id=<?= $dt['task_id'] ?>&project_id=<?= $project['id'] ?>" target="_blank">
                                        #<?= $dt['task_id'] ?>: <?= $this->text->e($dt['task_title']) ?>
                                    </a>
                                </div>
                                <ul class="delay-alert-reasons">
                                    <?php foreach ($dt['reasons'] as $reason): ?>
                                        <li><?= $this->text->e($reason) ?></li>
                                    <?php endforeach ?>
                                </ul>
                            </div>
                        <?php endforeach ?>
                    <?php endif ?>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    <?php endif ?>
</div>
