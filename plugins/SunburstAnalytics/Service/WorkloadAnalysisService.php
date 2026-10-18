<?php

namespace Kanboard\Plugin\SunburstAnalytics\Service;

use Kanboard\Core\Base;

class WorkloadAnalysisService extends Base
{
    public function getProjectWorkloadAnalysis($projectId)
    {
        $tasks = $this->db->table('tasks')
            ->columns(
                'tasks.id',
                'tasks.title',
                'tasks.owner_id',
                'tasks.is_active',
                'tasks.column_id',
                'tasks.date_due',
                'tasks.time_spent',
                'tasks.time_estimated',
                'users.name as assignee_name',
                'users.username as assignee_username',
                'columns.title as column_title'
            )
            ->join('users', 'id', 'owner_id')
            ->join('columns', 'id', 'column_id')
            ->eq('tasks.project_id', $projectId)
            ->findAll();

        $totalOpenTasks = 0;
        $userStats = [];
        $currentTime = time();

        foreach ($tasks as $task) {
            if ($task['is_active'] != 1) {
                continue;
            }

            $userId = (int)$task['owner_id'];
            if ($userId > 0) {
                $userName = !empty($task['assignee_name']) ? $task['assignee_name'] : $task['assignee_username'];
            } else {
                $userName = t('Unassigned (بدون مسئول)');
            }

            if (!isset($userStats[$userId])) {
                $userStats[$userId] = [
                    'user_id'          => $userId,
                    'user_name'        => $userName,
                    'open_tasks_count' => 0,
                    'task_percentage'  => 0,
                    'is_highest'       => false,
                    'columns'          => [], // تفکیک تسک‌ها بر اساس ستون
                    'delayed_tasks'    => [],
                ];
            }

            $userStats[$userId]['open_tasks_count']++;
            $totalOpenTasks++;

            // شمارش به تفکیک ستون
            $colTitle = !empty($task['column_title']) ? $task['column_title'] : t('No Column');
            if (!isset($userStats[$userId]['columns'][$colTitle])) {
                $userStats[$userId]['columns'][$colTitle] = 0;
            }
            $userStats[$userId]['columns'][$colTitle]++;

            // بررسی تاخیرها در ستون‌های در حال انجام (Work)
            $colTitleLower = strtolower($colTitle);
            $isWorkingColumn = (strpos($colTitleLower, 'work') !== false || strpos($colTitleLower, 'prog') !== false);

            if ($isWorkingColumn) {
                $reasons = [];
                if (!empty($task['date_due']) && $task['date_due'] < $currentTime) {
                    $delayDays = ceil(($currentTime - $task['date_due']) / 86400);
                    $reasons[] = t('Overdue by %d day(s)', $delayDays);
                }

                $timeSpent = (float)$task['time_spent'];
                $timeEst   = (float)$task['time_estimated'];
                if ($timeEst > 0 && $timeSpent > $timeEst) {
                    $overTime = round($timeSpent - $timeEst, 1);
                    $reasons[] = t('Time spent exceeded estimated by %s hours', $overTime);
                }

                if (!empty($reasons)) {
                    $userStats[$userId]['delayed_tasks'][] = [
                        'task_id'    => (int)$task['id'],
                        'task_title' => $task['title'],
                        'reasons'    => $reasons,
                    ];
                }
            }
        }

        // محاسبه درصدها و یافتن بیشترین بار کاری
        $maxOpenTasks = 0;
        foreach ($userStats as $userId => $stats) {
            $userStats[$userId]['task_percentage'] = ($totalOpenTasks > 0)
                ? round(($stats['open_tasks_count'] / $totalOpenTasks) * 100, 1)
                : 0;

            if ($userId > 0 && $stats['open_tasks_count'] > $maxOpenTasks) {
                $maxOpenTasks = $stats['open_tasks_count'];
            }
        }

        foreach ($userStats as $userId => $stats) {
            if ($userId > 0 && $stats['open_tasks_count'] === $maxOpenTasks && $maxOpenTasks > 0) {
                $userStats[$userId]['is_highest'] = true;
            }
        }

        // مرتب‌سازی بر اساس بیشترین تسک باز
        uasort($userStats, function ($a, $b) {
            return $b['open_tasks_count'] <=> $a['open_tasks_count'];
        });

        return [
            'total_open_tasks' => $totalOpenTasks,
            'users'            => $userStats,
        ];
    }
}
