<?php

namespace Kanboard\Plugin\SunburstAnalytics\Controller;

use Kanboard\Controller\BaseController;

/**
 * Class SunburstController
 * 
 * پردازشگر اختصاصی ساختار درختی نمودار خورشیدی پروژه اهواز 005
 */
class SunburstController extends BaseController
{
    public function show()
    {
        $project = $this->getProject();

        // واکشی ستون‌های استاندارد برای آگاهی از وضعیت‌ها
        $columns = $this->columnModel->getList($project['id']);

        // واکشی تسک‌های فعال و غیرفعال پروژه به همراه نام ستون و کاربر
        $tasks = $this->db->table('tasks')
            ->columns(
                'tasks.id',
                'tasks.title',
                'tasks.owner_id',
                'tasks.column_id',
                'tasks.is_active',
                'tasks.score',
                'tasks.time_estimated',
                'tasks.time_spent',
                'users.name as owner_name',
                'users.username as owner_username',
                'columns.title as column_title'
            )
            ->join('users', 'id', 'owner_id')
            ->join('columns', 'id', 'column_id')
            ->eq('tasks.project_id', $project['id'])
            ->findAll();

        // واکشی ساب‌تسک‌ها برای سنجش پیشرفت داخلی
        $allSubtasks = $this->db->table('subtasks')
            ->columns('task_id', 'status')
            ->findAll();

        $subtaskStats = [];
        foreach ($allSubtasks as $st) {
            $tid = $st['task_id'];
            if (!isset($subtaskStats[$tid])) {
                $subtaskStats[$tid] = ['total' => 0, 'done' => 0];
            }
            $subtaskStats[$tid]['total']++;
            if ($st['status'] == 2) {
                $subtaskStats[$tid]['done']++;
            }
        }

        // نقشه پالت رنگی استاندارد وضعیت‌ها
        $statusColors = [
            'backlog' => '#7f8c8d',      // خاکستری
            'ready'   => '#2980b9',      // آبی
            'work in progress' => '#e67e22', // نارنجی
            'working' => '#e67e22',      // نارنجی
            'done'    => '#27ae60',      // سبز
            'default' => '#34495e'       // سرمه‌ای
        ];

        // آمار کلی پروژه
        $totalProjectTasks = count($tasks);
        $projectOpenTasks = 0;
        $projectClosedTasks = 0;

        // ساختاربندی سلسله‌مراتبی داده‌ها
        $usersMap = [];
        foreach ($tasks as $task) {
            $isOpen = ($task['is_active'] == 1);
            if ($isOpen) {
                $projectOpenTasks++;
            } else {
                $projectClosedTasks++;
            }

            $uId = $task['owner_id'] ? $task['owner_id'] : 0;
            $uName = $task['owner_name'] ? $task['owner_name'] : ($task['owner_username'] ? $task['owner_username'] : 'بدون مسئول (Unassigned)');
            $cId = $task['column_id'];
            $cTitle = $task['column_title'] ? $task['column_title'] : 'نامشخص';

            if (!isset($usersMap[$uId])) {
                $usersMap[$uId] = [
                    'name' => $uName,
                    'total_open' => 0,
                    'total_closed' => 0,
                    'columns' => []
                ];
            }

            if ($isOpen) {
                $usersMap[$uId]['total_open']++;
            } else {
                $usersMap[$uId]['total_closed']++;
            }

            if (!isset($usersMap[$uId]['columns'][$cId])) {
                $usersMap[$uId]['columns'][$cId] = [
                    'title' => $cTitle,
                    'tasks' => []
                ];
            }

            $usersMap[$uId]['columns'][$cId]['tasks'][] = $task;
        }

        // محاسبه درصد پیشرفت کلی پروژه
        $projectProgress = $totalProjectTasks > 0 ? round(($projectClosedTasks / $totalProjectTasks) * 100) : 0;
        $projectCenterColor = $projectProgress >= 70 ? '#27ae60' : ($projectProgress >= 40 ? '#e67e22' : '#c0392b');

        // آرایه‌های ورودی پلات‌لی (Plotly Arrays)
        $ids = ['root'];
        $labels = [$project['name']];
        $parents = [''];
        $values = [max(1, $totalProjectTasks)];
        $colors = [$projectCenterColor];
        $hoverTexts = [
            "<b>پروژه: " . htmlspecialchars($project['name']) . "</b><br>" .
            "کل کارها: {$totalProjectTasks}<br>" .
            "کارهای باز (فعال): {$projectOpenTasks}<br>" .
            "کارهای بسته (انجام‌شده): {$projectClosedTasks}<br>" .
            "پیشرفت کل: {$projectProgress}%"
        ];
        $customData = [0];

        // ساخت گره‌های کاربران، ستون‌ها و تسک‌ها
        foreach ($usersMap as $uId => $uData) {
            $userNodeId = "user_" . $uId;
            $userTotal = $uData['total_open'] + $uData['total_closed'];

            $ids[] = $userNodeId;
            $labels[] = $uData['name'];
            $parents[] = 'root';
            $values[] = max(1, $userTotal);
            $colors[] = '#1abc9c'; // رنگ فیروزه‌ای تمیز برای حلقه کاربران
            $hoverTexts[] = "<b>کارشناس: {$uData['name']}</b><br>" .
                            "کل کارها: {$userTotal}<br>" .
                            "📌 کارهای باز: {$uData['total_open']}<br>" .
                            "✅ کارهای تکمیل: {$uData['total_closed']}";
            $customData[] = 0;

            // گره‌های ستون‌های مربوط به این کاربر
            foreach ($uData['columns'] as $cId => $cData) {
                $columnNodeId = "user_{$uId}_col_{$cId}";
                $colTaskCount = count($cData['tasks']);
                $cTitleLower = strtolower(trim($cData['title']));

                // تطبیق رنگ ستون
                $colColor = $statusColors['default'];
                foreach ($statusColors as $key => $hex) {
                    if (strpos($cTitleLower, $key) !== false) {
                        $colColor = $hex;
                        break;
                    }
                }

                $ids[] = $columnNodeId;
                $labels[] = $cData['title'] . " ({$colTaskCount})";
                $parents[] = $userNodeId;
                $values[] = max(1, $colTaskCount);
                $colors[] = $colColor;
                $hoverTexts[] = "<b>ستون: {$cData['title']}</b><br>" .
                                "مسئول: {$uData['name']}<br>" .
                                "تعداد کار در این ستون: {$colTaskCount}";
                $customData[] = 0;

                // گره‌های تسک‌ها (لایه نهایی)
                foreach ($cData['tasks'] as $task) {
                    $taskNodeId = "task_" . $task['id'];
                    $tWeight = !empty($task['score']) ? max(1, (int)$task['score']) : 1;

                    // وضعیت تکمیل ساب‌تسک‌ها
                    $subStats = isset($subtaskStats[$task['id']]) ? $subtaskStats[$task['id']] : ['total' => 0, 'done' => 0];
                    $subText = $subStats['total'] > 0 ? "ساب‌تسک‌ها: {$subStats['done']} از {$subStats['total']}" : "بدون ساب‌تسک";

                    $ids[] = $taskNodeId;
                    $labels[] = "#" . $task['id'] . " " . mb_strimwidth($task['title'], 0, 20, '...');
                    $parents[] = $columnNodeId;
                    $values[] = $tWeight;
                    // تسک دقیقاً رنگ ستون خود را می‌گیرد تا مدیر سریع تشخیص دهد
                    $colors[] = $colColor;
                    $hoverTexts[] = "<b>تسک #{$task['id']}: " . htmlspecialchars($task['title']) . "</b><br>" .
                                    "ستون: {$cData['title']}<br>" .
                                    "مسئول: {$uData['name']}<br>" .
                                    "{$subText}<br>" .
                                    "<i>(برای باز شدن کارت کلیک کنید)</i>";
                    $customData[] = (int)$task['id'];
                }
            }
        }

        $chartData = json_encode([
            'ids' => $ids,
            'labels' => $labels,
            'parents' => $parents,
            'values' => $values,
            'colors' => $colors,
            'hoverTexts' => $hoverTexts,
            'customData' => $customData,
            'projectId' => $project['id']
        ], JSON_UNESCAPED_UNICODE);

        $this->response->html($this->template->render('SunburstAnalytics:analytic/show', [
            'project'   => $project,
            'title'     => t('Sunburst Interactive Workload & Progress'),
            'chartData' => $chartData,
        ]));
    }
}
