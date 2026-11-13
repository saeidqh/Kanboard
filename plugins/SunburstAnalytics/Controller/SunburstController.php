<?php
namespace Kanboard\Plugin\SunburstAnalytics\Controller;

use Kanboard\Controller\BaseController;

class SunburstController extends BaseController
{
    public function show()
    {
        $project = $this->getProject();

        // 1. دریافت اطلاعات بار کاری کاربران
        $workloadData = $this->workloadAnalysisService
            ->getProjectWorkloadAnalysis($project['id']);

        // 2. ساخت داده‌های نمودار خورشیدی
        $chartData = $this->buildSunburstData($project['id']);

        // 3. دریافت تاریخ‌های فیلتر رتبه‌بندی
        // فرم باید تاریخ میلادی با قالب YYYY-MM-DD ارسال کند.
        $fromInput = trim($this->request->getStringParam('from_date'));
        $toInput = trim($this->request->getStringParam('to_date'));

        /*
         * تبدیل تاریخ میلادی به timestamp
         *
         * From Date: ابتدای روز
         * To Date: انتهای روز
         *
         * در این بخش نیازی به jdf.php نیست؛
         * تبدیل تاریخ شمسی به میلادی در JavaScript انجام می‌شود.
         */
        $parseDate = function ($dateStr, $isEndOfDay = false) {
            if ($dateStr === '') {
                return null;
            }

            // فقط قالب استاندارد تاریخ میلادی پذیرفته می‌شود.
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                return null;
            }

            $date = \DateTime::createFromFormat('!Y-m-d', $dateStr);

            // بررسی معتبر بودن تاریخ، از جمله روز و ماه
            if (
                $date === false ||
                $date->format('Y-m-d') !== $dateStr
            ) {
                return null;
            }

            if ($isEndOfDay) {
                $date->setTime(23, 59, 59);
            } else {
                $date->setTime(0, 0, 0);
            }

            return $date->getTimestamp();
        };

        // تاریخ شروع؛ در صورت خالی یا نامعتبر بودن، 30 روز قبل
        $fromTimestamp = $parseDate($fromInput, false);

        if ($fromTimestamp === null) {
            $fromTimestamp = strtotime('-30 days 00:00:00');
        }

        // تاریخ پایان؛ در صورت خالی یا نامعتبر بودن، زمان فعلی
        $toTimestamp = $parseDate($toInput, true);

        if ($toTimestamp === null) {
            $toTimestamp = time();
        }

        // اگر بازه تاریخ برعکس انتخاب شده باشد، آن را اصلاح می‌کنیم.
        if ($fromTimestamp > $toTimestamp) {
            $tempTimestamp = $fromTimestamp;
            $fromTimestamp = $toTimestamp;
            $toTimestamp = $tempTimestamp;
        }

        /*
         * تاریخ‌های میلادی برای مقدار اولیه فرم
         * JavaScript آن‌ها را برای نمایش به شمسی تبدیل می‌کند.
         */
        $displayFromDate = date('Y-m-d', $fromTimestamp);
        $displayToDate = date('Y-m-d', $toTimestamp);

        // 4. دریافت رتبه‌بندی تیم در بازه انتخاب‌شده
        $rankingData = $this->workloadAnalysisService
            ->getProjectTeamRanking(
                $project['id'],
                $fromTimestamp,
                $toTimestamp
            );

        // 5. نمایش صفحه تحلیل
        $this->response->html(
            $this->template->render(
                'SunburstAnalytics:analytic/show',
                [
                    'project'      => $project,
                    'title'        => t('Sunburst Analytics & Team Performance'),
                    'workloadData' => $workloadData,
                    'rankingData'  => $rankingData,
                    'chartData'    => $chartData,
                    'fromDate'     => $displayFromDate,
                    'toDate'       => $displayToDate,
                ]
            )
        );
    }

    private function buildSunburstData($projectId)
    {
        // تسک‌های پروژه با فیلتر سلسله‌مراتبی (Level 1 = همه؛ بقیه = خود + زیرمجموعه)
        $query = $this->db->table('tasks')
            ->columns(
                'id',
                'title',
                'owner_id',
                'column_id',
                'is_active',
                'time_estimated',
                'time_spent'
            )
            ->eq('project_id', $projectId);

        if (class_exists('\Kanboard\Plugin\HierarchicalRoles\Helper\HierarchicalRoleHelper')) {
            $helper = new \Kanboard\Plugin\HierarchicalRoles\Helper\HierarchicalRoleHelper($this->container);
            $scopeIds = $helper->getAnalyticsVisibleUserIds($projectId);
            if ($scopeIds !== null) {
                if (empty($scopeIds)) {
                    $query->eq('owner_id', -1);
                } else {
                    $query->in('owner_id', $scopeIds);
                }
            }
        }

        $tasks = $query->findAll();

        $columns = $this->columnModel->getList($projectId);
        $project = $this->projectModel->getById($projectId);
        $projectName = $project['name'] ?? 'Ahvaz005';

        // ساخت نقشه شناسه کاربران به نام آن‌ها
        $usersMap = [];
        $rawUsers = $this->db->table('users')
            ->columns('id', 'name', 'username')
            ->findAll();

        foreach ($rawUsers as $user) {
            $usersMap[$user['id']] = !empty($user['name'])
                ? $user['name']
                : $user['username'];
        }

        // شمارش تسک‌های باز و بسته پروژه
        $totalProjectTasks = count($tasks);
        $projectOpenTasks = 0;
        $projectClosedTasks = 0;

        $userBuckets = [];
        $userStats = [];

        foreach ($tasks as $task) {
            $ownerId = (int) $task['owner_id'];
            $colId = (int) $task['column_id'];

            if (!isset($userBuckets[$ownerId])) {
                $userBuckets[$ownerId] = [];
                $userStats[$ownerId] = [
                    'open' => 0,
                    'closed' => 0,
                ];
            }

            if (!isset($userBuckets[$ownerId][$colId])) {
                $userBuckets[$ownerId][$colId] = [];
            }

            $userBuckets[$ownerId][$colId][] = $task;

            if ((int) $task['is_active'] === 1) {
                $projectOpenTasks++;
                $userStats[$ownerId]['open']++;
            } else {
                $projectClosedTasks++;
                $userStats[$ownerId]['closed']++;
            }
        }

        $projectProgress = ($totalProjectTasks > 0)
            ? round(
                ($projectClosedTasks / $totalProjectTasks) * 100,
                1
            )
            : 0;

        // انتخاب رنگ ریشه پروژه براساس درصد پیشرفت
        if ($projectProgress >= 80) {
            $rootColor = '#27ae60';
        } elseif ($projectProgress >= 40) {
            $rootColor = '#2980b9';
        } elseif ($projectProgress > 0) {
            $rootColor = '#d35400';
        } else {
            $rootColor = '#34495e';
        }

        // رنگ‌های اختصاص‌یافته به کاربران
        $palette = [
            '#16a085',
            '#2980b9',
            '#8e44ad',
            '#2c3e50',
            '#f39c12',
            '#d35400',
            '#27ae60',
            '#7f8c8d',
        ];

        $userColorIndex = 0;

        $ids = ['root'];
        $labels = ["<b>{$projectName}</b>"];
        $parents = [''];
        $values = [0];
        $customData = [0];

        $hoverTexts = [
            "<b>پروژه: {$projectName}</b><br>" .
            "کل کارها: {$totalProjectTasks}<br>" .
            "📌 تسک‌های باز: {$projectOpenTasks}<br>" .
            "✔ تسک‌های بسته: {$projectClosedTasks}<br>" .
            "<b>درصد پیشرفت کل: {$projectProgress}%</b>",
        ];

        $colors = [$rootColor];
        $rootWeight = 0;

        // لایه‌ها: کارشناس، ستون وضعیت، تسک و ساب‌تسک
        foreach ($userBuckets as $ownerId => $colsData) {
            $userName = ($ownerId > 0)
                ? ($usersMap[$ownerId] ?? "کاربر {$ownerId}")
                : 'Unassigned';

            $userNodeId = "user_{$ownerId}";

            $uOpen = $userStats[$ownerId]['open'];
            $uClosed = $userStats[$ownerId]['closed'];
            $uTotal = $uOpen + $uClosed;

            $uProgress = ($uTotal > 0)
                ? round(($uClosed / $uTotal) * 100, 1)
                : 0;

            $uColor = ($ownerId > 0)
                ? $palette[$userColorIndex % count($palette)]
                : '#7f8c8d';

            $userColorIndex++;

            $userHover =
                "<b>کارشناس: {$userName}</b><br>" .
                "کل تسک‌ها: {$uTotal}<br>" .
                "📌 کارهای باز: {$uOpen}<br>" .
                "✔ کارهای بسته: {$uClosed}<br>" .
                "پیشرفت فردی: {$uProgress}%";

            // گره کارشناس
            $ids[] = $userNodeId;
            $labels[] = $userName;
            $parents[] = 'root';
            $customData[] = 0;
            $hoverTexts[] = $userHover;
            $colors[] = $uColor;

            $userWeightIndex = count($values);
            $values[] = 0;
            $userTotalWeight = 0;

            foreach ($colsData as $colId => $colTasks) {
                $colTitle = $columns[$colId] ?? "Column {$colId}";
                $colNodeId = "user_{$ownerId}_col_{$colId}";

                // رنگ ستون بر اساس نام وضعیت
                $colLower = strtolower($colTitle);

                if (strpos($colLower, 'done') !== false) {
                    $cColor = '#2ecc71';
                } elseif (strpos($colLower, 'work') !== false) {
                    $cColor = '#e74c3c';
                } elseif (strpos($colLower, 'ready') !== false) {
                    $cColor = '#f1c40f';
                } else {
                    $cColor = '#3498db';
                }

                // گره ستون وضعیت
                $ids[] = $colNodeId;
                $labels[] = $colTitle;
                $parents[] = $userNodeId;
                $customData[] = 0;

                $hoverTexts[] =
                    "<b>وضعیت: {$colTitle}</b><br>" .
                    'تسک‌های این بخش: ' . count($colTasks);

                $colors[] = $cColor;

                $colWeightIndex = count($values);
                $values[] = 0;
                $colTotalWeight = 0;

                foreach ($colTasks as $task) {
                    $taskId = (int) $task['id'];
                    $taskNodeId = "task_{$taskId}";

                    $taskTitle = (string) $task['title'];

                    $taskShortTitle = "#{$taskId} " .
                        mb_substr($taskTitle, 0, 20) .
                        (mb_strlen($taskTitle) > 20 ? '...' : '');

                    $subtasks = $this->subtaskModel->getAll($taskId);

                    // گره تسک
                    $ids[] = $taskNodeId;
                    $labels[] = $taskShortTitle;
                    $parents[] = $colNodeId;
                    $customData[] = $taskId;

                    $hoverTexts[] =
                        "<b>#{$taskId}: {$taskTitle}</b><br>" .
                        "مسئول: {$userName}<br>" .
                        "ستون: {$colTitle}<br>" .
                        '(برای باز شدن تسک کلیک کنید)';

                    $colors[] = ((int) $task['is_active'] === 1)
                        ? '#5dade2'
                        : '#95a5a6';

                    if (!empty($subtasks)) {
                        $taskWeightIndex = count($values);
                        $values[] = 0;
                        $taskTotalWeight = 0;

                        foreach ($subtasks as $subtask) {
                            $subtaskId = (int) $subtask['id'];

                            $subtaskWeight =
                                ($subtask['time_estimated'] > 0)
                                ? (float) $subtask['time_estimated']
                                : 1.0;

                            $subtaskTitle = (string) $subtask['title'];

                            $ids[] = "subtask_{$subtaskId}";
                            $labels[] = "↳ " .
                                mb_substr($subtaskTitle, 0, 16);

                            $parents[] = $taskNodeId;
                            $values[] = $subtaskWeight;
                            $customData[] = $taskId;

                            $subtaskStatus = (int) $subtask['status'];

                            $hoverTexts[] =
                                "<b>ساب‌تسک: {$subtaskTitle}</b><br>" .
                                'وضعیت: ' .
                                ($subtaskStatus === 2
                                    ? 'Done'
                                    : 'In Progress');

                            $colors[] = ($subtaskStatus === 2)
                                ? '#27ae60'
                                : '#f39c12';

                            $taskTotalWeight += $subtaskWeight;
                        }

                        $values[$taskWeightIndex] = $taskTotalWeight;
                        $colTotalWeight += $taskTotalWeight;
                    } else {
                        $taskWeight = ($task['time_estimated'] > 0)
                            ? (float) $task['time_estimated']
                            : 1.0;

                        $values[] = $taskWeight;
                        $colTotalWeight += $taskWeight;
                    }
                }

                $values[$colWeightIndex] = $colTotalWeight;
                $userTotalWeight += $colTotalWeight;
            }

            $values[$userWeightIndex] = $userTotalWeight;
            $rootWeight += $userTotalWeight;
        }

        $values[0] = $rootWeight;

        return json_encode(
            [
                'projectId' => (int) $projectId,
                'ids' => $ids,
                'labels' => $labels,
                'parents' => $parents,
                'values' => $values,
                'customData' => $customData,
                'hoverTexts' => $hoverTexts,
                'colors' => $colors,
            ],
            JSON_UNESCAPED_UNICODE
        );
    }
}