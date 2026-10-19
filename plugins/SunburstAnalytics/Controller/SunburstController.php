<?php

namespace Kanboard\Plugin\SunburstAnalytics\Controller;

use Kanboard\Controller\BaseController;

class SunburstController extends BaseController
{
        public function show()
{
    $project = $this->getProject();

    // ۱. داده بار کاری کاربران
    $workloadData = $this->workloadAnalysisService->getProjectWorkloadAnalysis($project['id']);

    // ۲. ساخت داده‌های نمودار خورشیدی
    $chartData = $this->buildSunburstData($project['id']);

    // ۳. فیلتر تاریخ رتبه‌بندی
    $fromInput = $this->request->getStringParam('from_date');
    $toInput = $this->request->getStringParam('to_date');

    $fromTimestamp = !empty($fromInput) ? strtotime($fromInput . ' 00:00:00') : strtotime('-30 days 00:00:00');
    $toTimestamp = !empty($toInput) ? strtotime($toInput . ' 23:59:59') : time();

    // ۴. داده رتبه‌بندی تیم
    $rankingData = $this->workloadAnalysisService->getProjectTeamRanking(
        $project['id'],
        $fromTimestamp,
        $toTimestamp
    );

    $this->response->html($this->template->render('SunburstAnalytics:analytic/show', [
        'project'      => $project,
        'title'        => t('Sunburst Analytics & Team Performance'),
        'workloadData' => $workloadData,
        'rankingData'  => $rankingData,
        'chartData'    => $chartData,
        'fromDate'     => date('Y-m-d', $fromTimestamp),  // ✅ اضافه شد
        'toDate'       => date('Y-m-d', $toTimestamp),    // ✅ اضافه شد
    ]));
}


    private function buildSunburstData($projectId)
    {
        // دریافت تمام تسک‌های پروژه (فعال و بسته)
        $tasks = $this->db->table('tasks')
            ->columns('id', 'title', 'owner_id', 'column_id', 'is_active', 'time_estimated', 'time_spent')
            ->eq('project_id', $projectId)
            ->findAll();

        $columns = $this->columnModel->getList($projectId);
        $project = $this->projectModel->getById($projectId);
        $projectName = $project['name'] ?? 'Ahvaz005';

        // دریافت لیست کاربران دارای تسک
        $usersMap = [];
        $rawUsers = $this->db->table('users')->columns('id', 'name', 'username')->findAll();
        foreach ($rawUsers as $u) {
            $usersMap[$u['id']] = !empty($u['name']) ? $u['name'] : $u['username'];
        }

        // تفکیک تسک‌ها و شمارش وضعیت کلی پروژه
        $totalProjectTasks = count($tasks);
        $projectOpenTasks = 0;
        $projectClosedTasks = 0;

        $userBuckets = []; // [owner_id => [column_id => [tasks]]]
        $userStats   = []; // [owner_id => ['open' => x, 'closed' => y]]

        foreach ($tasks as $task) {
            $ownerId = (int)$task['owner_id'];
            $colId   = (int)$task['column_id'];

            if (!isset($userBuckets[$ownerId])) {
                $userBuckets[$ownerId] = [];
                $userStats[$ownerId] = ['open' => 0, 'closed' => 0];
            }
            if (!isset($userBuckets[$ownerId][$colId])) {
                $userBuckets[$ownerId][$colId] = [];
            }
            $userBuckets[$ownerId][$colId][] = $task;

            if ($task['is_active'] == 1) {
                $projectOpenTasks++;
                $userStats[$ownerId]['open']++;
            } else {
                $projectClosedTasks++;
                $userStats[$ownerId]['closed']++;
            }
        }

        $projectProgress = ($totalProjectTasks > 0)
            ? round(($projectClosedTasks / $totalProjectTasks) * 100, 1)
            : 0;

        // انتخاب رنگ هسته پروژه براساس درصد پیشرفت
        if ($projectProgress >= 80) {
            $rootColor = '#27ae60'; // سبز موفقیت‌آمیز
        } elseif ($projectProgress >= 40) {
            $rootColor = '#2980b9'; // آبی پیشرفت پایدار
        } elseif ($projectProgress > 0) {
            $rootColor = '#d35400'; // نارنجی شروع به کار
        } else {
            $rootColor = '#34495e'; // زغالی تیره اولیه
        }

        // پالت رنگ‌های شکیل برای کارشناسان
        $palette = ['#16a085', '#2980b9', '#8e44ad', '#2c3e50', '#f39c12', '#d35400', '#27ae60', '#7f8c8d'];
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
            "<b>درصد پیشرفت کل: {$projectProgress}%</b>"
        ];
        $colors = [$rootColor];

        $rootWeight = 0;

        // ساخت لایه‌ها: لایه ۲ (کارشناس) -> لایه ۳ (ستون) -> لایه ۴ (تسک) -> لایه ۵ (ساب‌تسک)
        foreach ($userBuckets as $ownerId => $colsData) {
            $userName = ($ownerId > 0) ? ($usersMap[$ownerId] ?? "کاربر {$ownerId}") : 'Unassigned';
            $userNodeId = "user_{$ownerId}";
            $uOpen   = $userStats[$ownerId]['open'];
            $uClosed = $userStats[$ownerId]['closed'];
            $uTotal  = $uOpen + $uClosed;
            $uProgress = ($uTotal > 0) ? round(($uClosed / $uTotal) * 100, 1) : 0;

            $uColor = ($ownerId > 0) ? $palette[$userColorIndex % count($palette)] : '#7f8c8d';
            $userColorIndex++;

            $userHover = "<b>کارشناس: {$userName}</b><br>" .
                         "کل تسک‌ها: {$uTotal}<br>" .
                         "📌 کارهای باز: {$uOpen}<br>" .
                         "✔ کارهای بسته: {$uClosed}<br>" .
                         "پیشرفت فردی: {$uProgress}%";

            // افزودن گره کارشناس (لایه ۲)
            $ids[] = $userNodeId;
            $labels[] = $userName;
            $parents[] = 'root';
            $customData[] = 0;
            $hoverTexts[] = $userHover;
            $colors[] = $uColor;

            $userWeightIndex = count($values);
            $values[] = 0; // وزن بعداً از مجموع فرزندان محاسبه و پر می‌شود
            $userTotalWeight = 0;

            foreach ($colsData as $colId => $colTasks) {
                $colTitle = $columns[$colId] ?? "Column {$colId}";
                $colNodeId = "user_{$ownerId}_col_{$colId}";

                // تعیین رنگ ستون کارشناس
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

                // افزودن گره ستون وضعیت (لایه ۳)
                $ids[] = $colNodeId;
                $labels[] = $colTitle;
                $parents[] = $userNodeId;
                $customData[] = 0;
                $hoverTexts[] = "<b>وضعیت: {$colTitle}</b><br>تسک‌های این بخش: " . count($colTasks);
                $colors[] = $cColor;

                $colWeightIndex = count($values);
                $values[] = 0;
                $colTotalWeight = 0;

                foreach ($colTasks as $t) {
                    $taskId = (int)$t['id'];
                    $taskNodeId = "task_{$taskId}";
                    $taskShortTitle = "#{$taskId} " . mb_substr($t['title'], 0, 20) . (mb_strlen($t['title']) > 20 ? '...' : '');

                    $subtasks = $this->subtaskModel->getAll($taskId);

                    $ids[] = $taskNodeId;
                    $labels[] = $taskShortTitle;
                    $parents[] = $colNodeId;
                    $customData[] = $taskId;
                    $hoverTexts[] = "<b>#{$taskId}: {$t['title']}</b><br>مسئول: {$userName}<br>ستون: {$colTitle}<br>(برای باز شدن تسک کلیک کنید)";
                    $colors[] = ($t['is_active'] == 1) ? '#5dade2' : '#95a5a6';

                    if (!empty($subtasks)) {
                        $taskWeightIndex = count($values);
                        $values[] = 0;
                        $taskTotalWeight = 0;

                        foreach ($subtasks as $st) {
                            $stId = (int)$st['id'];
                            $stWeight = ($st['time_estimated'] > 0) ? (float)$st['time_estimated'] : 1.0;

                            $ids[] = "subtask_{$stId}";
                            $labels[] = "↳ " . mb_substr($st['title'], 0, 16);
                            $parents[] = $taskNodeId;
                            $values[] = $stWeight;
                            $customData[] = $taskId;
                            $hoverTexts[] = "<b>ساب‌تسک: {$st['title']}</b><br>وضعیت: " . ($st['status'] == 2 ? 'Done' : 'In Progress');
                            $colors[] = ($st['status'] == 2) ? '#27ae60' : '#f39c12';

                            $taskTotalWeight += $stWeight;
                        }
                        $values[$taskWeightIndex] = $taskTotalWeight;
                        $colTotalWeight += $taskTotalWeight;
                    } else {
                        $tWeight = ($t['time_estimated'] > 0) ? (float)$t['time_estimated'] : 1.0;
                        $values[] = $tWeight;
                        $colTotalWeight += $tWeight;
                    }
                }

                $values[$colWeightIndex] = $colTotalWeight;
                $userTotalWeight += $colTotalWeight;
            }

            $values[$userWeightIndex] = $userTotalWeight;
            $rootWeight += $userTotalWeight;
        }

        $values[0] = $rootWeight;

        return json_encode([
            'projectId'  => (int)$projectId,
            'ids'        => $ids,
            'labels'     => $labels,
            'parents'    => $parents,
            'values'     => $values,
            'customData' => $customData,
            'hoverTexts' => $hoverTexts,
            'colors'     => $colors
        ], JSON_UNESCAPED_UNICODE);
    }
}
