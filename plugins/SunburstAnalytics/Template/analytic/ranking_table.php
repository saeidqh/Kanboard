<div class="ranking-container" style="text-align: left; direction: ltr;">
    <!-- Date Filter Form -->
    <div class="ranking-filter-card">
        <form method="get" action="<?= $this->url->href('SunburstController', 'show', ['plugin' => 'SunburstAnalytics', 'project_id' => $project['id']]) ?>" class="ranking-filter-form">
            <input type="hidden" name="plugin" value="SunburstAnalytics">
            <input type="hidden" name="controller" value="SunburstController">
            <input type="hidden" name="action" value="show">
            <input type="hidden" name="project_id" value="<?= (int)$project['id'] ?>">

            <div class="filter-group">
                <label for="from_date"><i class="fa fa-calendar"></i> From Date:</label>
                <input type="date" id="from_date" name="from_date" value="<?= $this->text->e($fromDate) ?>" class="form-input-date">
            </div>

            <div class="filter-group">
                <label for="to_date"><i class="fa fa-calendar"></i> To Date:</label>
                <input type="date" id="to_date" name="to_date" value="<?= $this->text->e($toDate) ?>" class="form-input-date">
            </div>

            <button type="submit" class="btn btn-blue filter-btn">
                <i class="fa fa-filter"></i> Apply Filter
            </button>
        </form>
    </div>

    <!-- Ranking Table Card -->
    <div class="ranking-card">
        <div class="ranking-card-header">
            <h3><i class="fa fa-trophy"></i> Team Performance Ranking (Ahvaz005)</h3>
            <span class="ranking-subtitle">Evaluation Formula: Complexity Score (50%) + Timeliness (30%) + Done Count (20%)</span>
        </div>

        <table class="table-striped ranking-table">
            <thead>
                <tr>
                    <th style="width: 70px; text-align: center;">Rank</th>
                    <th>Team Member</th>
                    <th style="text-align: center;">Active Tasks (WIP)</th>
                    <th style="text-align: center;">Done Tasks</th>
                    <th style="text-align: center;">Complexity (Score)</th>
                    <th style="text-align: center;">On-Time Rate</th>
                    <th style="text-align: center;">Performance Score</th>
                    <th style="text-align: center;">Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rankingData['users'])): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 25px; color: #7f8c8d;">
                            No completed tasks found in the selected date range.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rankingData['users'] as $user): ?>
                        <tr>
                            <td style="text-align: center; font-weight: bold;">
                                <?php if ($user['rank'] === 1): ?>
                                    <span class="medal gold" title="1st Place">🥇 1</span>
                                <?php elseif ($user['rank'] === 2): ?>
                                    <span class="medal silver" title="2nd Place">🥈 2</span>
                                <?php elseif ($user['rank'] === 3): ?>
                                    <span class="medal bronze" title="3rd Place">🥉 3</span>
                                <?php else: ?>
                                    <span class="rank-number"><?= $user['rank'] ?></span>
                                <?php endif; ?>
                            </td>

                            <td style="font-weight: 600; color: #2c3e50;">
                                <?= $this->text->e($user['user_name']) ?>
                            </td>

                            <!-- Active WIP Context -->
                            <td style="text-align: center;">
                                <span class="badge-wip-context"><?= (int)$user['active_wip'] ?></span>
                            </td>

                            <td style="text-align: center; font-weight: 600;">
                                <?= (int)$user['done_count'] ?>
                            </td>

                            <td style="text-align: center;">
                                <span class="score-badge"><?= (int)$user['total_score'] ?> pts</span>
                            </td>

                            <td style="text-align: center;">
                                <div class="ontime-container">
                                    <div class="ontime-bar-bg">
                                        <div class="ontime-bar-fill" style="width: <?= $user['on_time_rate'] ?>%;"></div>
                                    </div>
                                    <span class="ontime-text"><?= $user['on_time_rate'] ?>%</span>
                                </div>
                            </td>

                            <td style="text-align: center;">
                                <span class="final-score-val"><?= number_format($user['final_score'], 1) ?></span>
                            </td>

                            <td style="text-align: center;">
                                <span class="grade-badge <?= $user['grade_class'] ?>">
                                    <?= $user['grade'] ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
