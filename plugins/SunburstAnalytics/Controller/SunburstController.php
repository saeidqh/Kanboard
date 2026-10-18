<?php

namespace Kanboard\Plugin\SunburstAnalytics\Controller;

use Kanboard\Controller\BaseController;

class SunburstController extends BaseController
{
    public function show()
    {
        $project = $this->getProject();

        // واکشی تمام تسک‌های فعال و دسته‌بندی‌شده
        $tasks = $this->db->table('tasks')
            ->columns(
                'tasks.id',
                'tasks.title',
                'tasks.owner_id',
                'tasks.column_id',
                'tasks.score',
                'tasks.time_spent',
                'tasks.time_estimated',
                'users.name as owner_name',
                'users.username as owner_username',
                'columns.title as column_title'
            )
            ->left('users', 'users', 'id', 'tasks', 'owner_id')
            ->left('columns', 'columns', 'id', 'tasks', 'column_id')
            ->eq('tasks.project_id', $project['id'])
            ->eq('tasks.is_active', 1)
            ->findAll();

        // واکشی وضعیت ساب‌تسک‌ها برای محاسبه دقیق درصد پیشرفت
        $subtasks = $this->db->table('subtasks')
            ->columns('task_id', 'status')
            ->findAll();

        $subtaskStats = [];
        foreach ($subtasks as $st) {
            $tid = $st['task_id'];
            if (!isset($subtaskStats[$tid])) {
                $subtaskStats[$tid] = ['total' => 0, 'done' => 0];
            }
            $subtaskStats[$tid]['total']++;
            if ($st['status'] == 2) {
                $subtaskStats[$tid]['done']++;
            }
        }

        // ساخت داده‌های درختی برای Plotly Sunburst
        // هر نود شامل: id, label, parent, value (وزن/تعداد), progress
        $ids = ['root'];
        $labels = [$project['name']];
        $parents = [''];
        $values = [0];
        $colors = [100];
        $hoverTexts = ['Project: ' . $project['name']];
        $customData = [0]; // برای ذخیره task_id جهت کلیک

        $userNodes = [];
        $colNodes = [];

        foreach ($tasks as $task) {
            $userName = !empty($task['owner_name']) ? $task['owner_name'] : (!empty($task['owner_username']) ? $task['owner_username'] : 'Unassigned (بدون مسئول)');
            $userId = 'u_' . $task['owner_id'];
            $colName = $task['column_title'];
            $colId = $userId . '_c_' . $task['column_id'];
            $taskId = 't_' . $task['id'];

            // وزن تسک بر پایه Complexity یا ۱
            $weight = ($task['score'] > 0) ? (int)$task['score'] : 1;

            // محاسبه درصد پیشرفت
            $tid = $task['id'];
            $progress = 0;
            if (isset($subtaskStats[$tid]) && $subtaskStats[$tid]['total'] > 0) {
                $progress = round(($subtaskStats[$tid]['done'] / $subtaskStats[$tid]['total']) * 100);
            } elseif ($colName == 'Done') {
                $progress = 100;
            } elseif ($task['time_estimated'] > 0) {
                $progress = min(100, round(($task['time_spent'] / $task['time_estimated']) * 100));
            } elseif ($colName == 'Work in progress') {
                $progress = 50;
            }

            // لایه ۱: کاربر
            if (!isset($userNodes[$userId])) {
                $userNodes[$userId] = true;
                $ids[] = $userId;
                $labels[] = $userName;
                $parents[] = 'root';
                $values[] = 0;
                $colors[] = 50;
                $hoverTexts[] = 'کاربر: ' . $userName;
                $customData[] = 0;
            }

            // لایه ۲: ستون زیرمجموعه کاربر
            if (!isset($colNodes[$colId])) {
                $colNodes[$colId] = true;
                $ids[] = $colId;
                $labels[] = $colName;
                $parents[] = $userId;
                $values[] = 0;
                $colors[] = ($colName == 'Done') ? 100 : (($colName == 'Work in progress') ? 50 : 10);
                $hoverTexts[] = $userName . ' ➔ ستون ' . $colName;
                $customData[] = 0;
            }

            // لایه ۳: تسک اختصاصی
            $ids[] = $taskId;
            $labels[] = '#' . $task['id'] . ' ' . mb_substr($task['title'], 0, 22) . (mb_strlen($task['title']) > 22 ? '...' : '');
            $parents[] = $colId;
            $values[] = $weight;
            $colors[] = $progress;
            $hoverTexts[] = "<b>#{$task['id']}: {$task['title']}</b><br>پیشرفت: {$progress}%<br>وزن: {$weight}<br><i>(برای باز شدن تسک کلیک کنید)</i>";
            $customData[] = (int)$task['id'];
        }

        $this->response->html($this->helper->layout->analytic('SunburstAnalytics:analytic/show', [
            'project' => $project,
            'title' => t('Sunburst Chart') . ' - ' . $project['name'],
            'chartData' => json_encode([
                'ids' => $ids,
                'labels' => $labels,
                'parents' => $parents,
                'values' => $values,
                'colors' => $colors,
                'hoverTexts' => $hoverTexts,
                'customData' => $customData,
            ]),
        ]));
    }
}
