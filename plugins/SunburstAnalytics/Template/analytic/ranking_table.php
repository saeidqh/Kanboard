<div class="ranking-container" style="text-align: left; direction: ltr;">

    <!-- Date Filter Form -->
    <div class="ranking-filter-card">
        <form
            method="get"
            action="<?= $this->url->href('SunburstController', 'show', [
                'plugin' => 'SunburstAnalytics',
                'project_id' => $project['id']
            ]) ?>"
            class="ranking-filter-form"
        >
            <input type="hidden" name="plugin" value="SunburstAnalytics">
            <input type="hidden" name="controller" value="SunburstController">
            <input type="hidden" name="action" value="show">
            <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">

            <!-- From Date -->
            <div class="filter-group">
                <label for="from_date_display">
                    <i class="fa fa-calendar"></i> From Date:
                </label>

                <!-- Visible Jalali date field -->
                <input
                    type="text"
                    id="from_date_display"
                    value="<?= $this->text->e($fromDate) ?>"
                    class="form-input-date persian-datepicker"
                    placeholder="YYYY/MM/DD"
                    autocomplete="off"
                >

                <!-- Hidden Gregorian date sent to the controller -->
                <input
                    type="hidden"
                    id="from_date"
                    name="from_date"
                    value="<?= $this->text->e($fromDate) ?>"
                >
            </div>

            <!-- To Date -->
            <div class="filter-group">
                <label for="to_date_display">
                    <i class="fa fa-calendar"></i> To Date:
                </label>

                <!-- Visible Jalali date field -->
                <input
                    type="text"
                    id="to_date_display"
                    value="<?= $this->text->e($toDate) ?>"
                    class="form-input-date persian-datepicker"
                    placeholder="YYYY/MM/DD"
                    autocomplete="off"
                >

                <!-- Hidden Gregorian date sent to the controller -->
                <input
                    type="hidden"
                    id="to_date"
                    name="to_date"
                    value="<?= $this->text->e($toDate) ?>"
                >
            </div>

            <button type="submit" class="btn btn-blue filter-btn">
                <i class="fa fa-filter"></i> Apply Filter
            </button>
        </form>
    </div>

    <!-- Ranking Table Card -->
    <div class="ranking-card">
        <div class="ranking-card-header">
            <h3>
                <i class="fa fa-trophy"></i>
                Team Performance Ranking (Ahvaz005)
            </h3>

            <span class="ranking-subtitle">
                Evaluation Formula: Complexity Score (50%) + Timeliness (30%) + Done Count (20%)
            </span>
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
                        <td
                            colspan="8"
                            style="text-align: center; padding: 25px; color: #7f8c8d;"
                        >
                            No completed tasks found in the selected date range.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rankingData['users'] as $user): ?>
                        <tr>
                            <!-- Rank -->
                            <td style="text-align: center; font-weight: bold;">
                                <?php if ((int) $user['rank'] === 1): ?>
                                    <span class="medal gold" title="1st Place">🥇 1</span>
                                <?php elseif ((int) $user['rank'] === 2): ?>
                                    <span class="medal silver" title="2nd Place">🥈 2</span>
                                <?php elseif ((int) $user['rank'] === 3): ?>
                                    <span class="medal bronze" title="3rd Place">🥉 3</span>
                                <?php else: ?>
                                    <span class="rank-number">
                                        <?= (int) $user['rank'] ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Team Member -->
                            <td style="font-weight: 600; color: #2c3e50;">
                                <?= $this->text->e($user['user_name']) ?>
                            </td>

                            <!-- Active WIP -->
                            <td style="text-align: center;">
                                <span class="badge-wip-context">
                                    <?= (int) $user['active_wip'] ?>
                                </span>
                            </td>

                            <!-- Completed Tasks -->
                            <td style="text-align: center; font-weight: 600;">
                                <?= (int) $user['done_count'] ?>
                            </td>

                            <!-- Complexity Score -->
                            <td style="text-align: center;">
                                <span class="score-badge">
                                    <?= (int) $user['total_score'] ?> pts
                                </span>
                            </td>

                            <!-- On-Time Rate -->
                            <td style="text-align: center;">
                                <div class="ontime-container">
                                    <div class="ontime-bar-bg">
                                        <div
                                            class="ontime-bar-fill"
                                            style="width: <?= max(0, min(100, (float) $user['on_time_rate'])) ?>%;"
                                        ></div>
                                    </div>

                                    <span class="ontime-text">
                                        <?= $this->text->e((string) $user['on_time_rate']) ?>%
                                    </span>
                                </div>
                            </td>

                            <!-- Final Performance Score -->
                            <td style="text-align: center;">
                                <span class="final-score-val">
                                    <?= number_format((float) $user['final_score'], 1) ?>
                                </span>
                            </td>

                            <!-- Grade -->
                            <td style="text-align: center;">
                                <span class="grade-badge <?= $this->text->e($user['grade_class']) ?>">
                                    <?= $this->text->e($user['grade']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
```