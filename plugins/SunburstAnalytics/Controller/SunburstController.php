<?php

namespace Kanboard\Plugin\SunburstAnalytics\Controller;

use Kanboard\Controller\BaseController;

/**
 * Class SunburstController
 * 
 * کنترلر نمودار خورشیدی (توزیع تسک‌ها و پیشرفت پروژه اهواز 005)
 */
class SunburstController extends BaseController
{
    public function show()
    {
        $project = $this->getProject();

        // واکشی تسک‌های فعال و غیرفعال پروژه به همراه نام ستون و کاربر
        $tasks = $this->db->table('tasks')
            ->columns(
                'tasks.id',
                'tasks.title',
                'tasks.owner_id',
                'tasks.column_id',
                'tasks.is_active',
                'tasks.score',
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

        // پالت رنگی استاندارد ستون‌ها
        $statusColors = [
            'backlog' => '#7f8c8d',      // خاکستری
            'ready'   => '#2980b9',      // آبی
            'work in progress' => '#e67e22', // نارنجی
            'working' => '#e67e22',      // نارنجی
            'done'    => '#27ae60',      // سبز
            'default' => '#34495e'       // سرمه‌ای
        ];

        $totalProjectTasks = count($tasks);
        $projectOpenTasks = 0;
        $projectClosedTasks = 0;

        // گروه‌بندی داده‌ها بر اساس کاربر و ستون
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

        $projectProgress = $totalProjectTasks > 0 ? round(($projectClosedTasks / $totalProjectTasks) * 100) : 0;
        $projectCenterColor = $projectProgress >= 70 ? '#27ae60' : ($projectProgress >= 40 ? '#e67e22' : '#c0392b');

        // ساخت آرایه‌های پلات‌لی (با وزن‌دهی صفر برای والدین تا نمودار خودکار ۳۶۰ درجه پر شود)
        $ids = ['root'];
        $labels = [$project['name']];
        $parents = [''];
        $values = [0]; // ریشه وزن صفر دارد تا فرزندان کل ۳۶۰ درجه را تقسیم کنند
        $colors = [$projectCenterColor];
        $hoverTexts = [
            "<b>پروژه: " . htmlspecialchars($project['name']) . "</b><br>" .
            "کل کارها: {$totalProjectTasks}<br>" .
            "کارهای باز: {$projectOpenTasks}<br>" .
            "کارهای بسته: {$projectClosedTasks}<br>" .
            "پیشرفت کل: {$projectProgress}%"
        ];
        $customData = [0];

        foreach ($usersMap as $uId => $uData) {
            $userNodeId = "user_" . $uId;
            $userTotal = $uData['total_open'] + $uData['total_closed'];

            $ids[] = $userNodeId;
            $labels[] = $uData['name'];
            $parents[] = 'root';
            $values[] = 0; // کاربر وزن صفر دارد تا ستون‌ها فضا را پر کنند
            $colors[] = '#1abc9c';
            $hoverTexts[] = "<b>کارشناس: {$uData['name']}</b><br>" .
                            "کل کارها: {$userTotal}<br>" .
                            "📌 کارهای باز: {$uData['total_open']}<br>" .
                            "✅ کارهای تکمیل: {$uData['total_closed']}";
            $customData[] = 0;

            foreach ($uData['columns'] as $cId => $cData) {
                $columnNodeId = "user_{$uId}_col_{$cId}";
                $colTaskCount = count($cData['tasks']);
                $cTitleLower = strtolower(trim($cData['title']));

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
                $values[] = 0; // ستون وزن صفر دارد تا تسک‌ها فضا را پر کنند
                $colors[] = $colColor;
                $hoverTexts[] = "<b>ستون: {$cData['title']}</b><br>" .
                                "مسئول: {$uData['name']}<br>" .
                                "تعداد کار: {$colTaskCount}";
                $customData[] = 0;

                foreach ($cData['tasks'] as $task) {
                    $taskNodeId = "task_" . $task['id'];
                    $tWeight = 1; // تسک‌ها وزن ۱ دارند تا ۳۶۰ درجه به تساوی تقسیم شود و سفیدی نماند

                    $subStats = isset($subtaskStats[$task['id']]) ? $subtaskStats[$task['id']] : ['total' => 0, 'done' => 0];
                    $subText = $subStats['total'] > 0 ? "ساب‌تسک‌ها: {$subStats['done']} از {$subStats['total']}" : "بدون ساب‌تسک";

                    $ids[] = $taskNodeId;
                    $labels[] = "#" . $task['id'] . " " . mb_strimwidth($task['title'], 0, 18, '...');
                    $parents[] = $columnNodeId;
                    $values[] = $tWeight;
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
