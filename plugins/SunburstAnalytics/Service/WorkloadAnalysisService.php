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
    $userId = !empty($task['owner_id']) ? (int)$task['owner_id'] : 0;

    if ($userId === 0) {
        $userName = 'Unassigned';
    } else {
        $userName = !empty($task['assignee_name']) ? $task['assignee_name'] : $task['assignee_username'];
    }

    if (!isset($userStats[$userId])) {
        $userStats[$userId] = [
            'user_id' => $userId,
            'user_name' => $userName,
            'open_tasks_count' => 0,
            'task_percentage' => 0,
            'is_highest' => false,
            'columns' => [], // ذخیره ستون‌ها برای نمایش در آکاردئون
            'delayed_tasks' => []
        ];
    }

    $colTitle = trim($task['column_title'] ?? 'Other');

    // مقداردهی اولیه برای هر ستونِ این کاربر
    if (!isset($userStats[$userId]['columns'][$colTitle])) {
        $userStats[$userId]['columns'][$colTitle] = 0;
    }

    // اگر تسک غیرفعال/بسته بود (is_active = 0)
    if ($task['is_active'] != 1) {
        // شمارش در آکاردئون (مثلاً در ستون Done یا ستون مربوطه)
        $userStats[$userId]['columns'][$colTitle]++;
        // خارج شدن از ادامه حلقه تا درصدها، Open Tasks و هشدارهای تاخیر دست‌نخورده بمانند
        continue;
    }

    // تسک‌های فعال (is_active = 1):
    $userStats[$userId]['columns'][$colTitle]++;
    $userStats[$userId]['open_tasks_count']++;
    $totalOpenTasks++;



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
        /**
     * Calculate Team Performance Ranking based on:
     * - Execution Power (Score / Complexity): 50%
     * - Timeliness (On-Time Delivery): 30%
     * - Throughput (Completed Tasks Count): 20%
     * Plus Active WIP as context.
     *
     * @param int $projectId
     * @param int|null $fromTimestamp
     * @param int|null $toTimestamp
     * @return array
     */
    public function getProjectTeamRanking($projectId, $fromTimestamp = null, $toTimestamp = null)
    {
        // Default range: last 30 days if not set
        if (!$toTimestamp) {
            $toTimestamp = time();
        }
        if (!$fromTimestamp) {
            $fromTimestamp = strtotime('-30 days', $toTimestamp);
        }

        // 1. Fetch Completed Tasks in the date range
        $completedTasksQuery = $this->db->table('tasks')
            ->columns(
                'tasks.id',
                'tasks.title',
                'tasks.owner_id',
                'tasks.score',
                'tasks.date_completed',
                'tasks.date_due',
                'users.username',
                'users.name as assignee_name'
            )
            ->join('users', 'id', 'owner_id')
            ->eq('tasks.project_id', $projectId)
            ->neq('tasks.owner_id', 0) // exclude unassigned
            ->gte('tasks.date_completed', $fromTimestamp)
            ->lte('tasks.date_completed', $toTimestamp);

        $completedTasks = $completedTasksQuery->findAll();

        // 2. Fetch Active WIP count for context
        $activeWipQuery = $this->db->table('tasks')
            ->columns('owner_id', 'COUNT(id) as active_count')
            ->eq('project_id', $projectId)
            ->eq('is_active', 1)
            ->neq('owner_id', 0)
            ->groupBy('owner_id')
            ->findAll();

        $activeWipMap = [];
        foreach ($activeWipQuery as $wip) {
            $activeWipMap[$wip['owner_id']] = (int)$wip['active_count'];
        }

        // 3. Aggregate stats per user
        $rankingData = [];
        foreach ($completedTasks as $task) {
            $uid = (int)$task['owner_id'];
            $displayName = !empty($task['assignee_name']) ? $task['assignee_name'] : $task['username'];

            if (!isset($rankingData[$uid])) {
                $rankingData[$uid] = [
                    'user_id' => $uid,
                    'user_name' => $displayName,
                    'done_count' => 0,
                    'total_score' => 0,
                    'on_time_count' => 0,
                    'active_wip' => isset($activeWipMap[$uid]) ? $activeWipMap[$uid] : 0,
                ];
            }

            $rankingData[$uid]['done_count']++;
            $rankingData[$uid]['total_score'] += (int)($task['score'] ?? 0);

            // On-time check: date_due exists and completed on or before due date
            if (!empty($task['date_due'])) {
                if ($task['date_completed'] <= $task['date_due']) {
                    $rankingData[$uid]['on_time_count']++;
                }
            } else {
                // If no deadline set, considered completed on time
                $rankingData[$uid]['on_time_count']++;
            }
        }

        // Add users who have active tasks but no done tasks yet in this range
        foreach ($activeWipMap as $uid => $wipCount) {
            if (!isset($rankingData[$uid])) {
                $user = $this->db->table('users')->columns('username', 'name')->eq('id', $uid)->findOne();
                if ($user) {
                    $displayName = !empty($user['name']) ? $user['name'] : $user['username'];
                    $rankingData[$uid] = [
                        'user_id' => $uid,
                        'user_name' => $displayName,
                        'done_count' => 0,
                        'total_score' => 0,
                        'on_time_count' => 0,
                        'active_wip' => $wipCount,
                    ];
                }
            }
        }

        if (empty($rankingData)) {
            return [
                'users' => [],
                'from_timestamp' => $fromTimestamp,
                'to_timestamp' => $toTimestamp,
            ];
        }

        // 4. Find maximum values for normalization (scale 0 - 100)
        $maxScore = 0;
        $maxCount = 0;
        foreach ($rankingData as $item) {
            if ($item['total_score'] > $maxScore) {
                $maxScore = $item['total_score'];
            }
            if ($item['done_count'] > $maxCount) {
                $maxCount = $item['done_count'];
            }
        }

        // 5. Calculate Final Weighted Score (0 to 100)
        foreach ($rankingData as $uid => &$item) {
            $normScore = $maxScore > 0 ? ($item['total_score'] / $maxScore) * 100 : 0;
            $normCount = $maxCount > 0 ? ($item['done_count'] / $maxCount) * 100 : 0;
            $onTimeRate = $item['done_count'] > 0 ? ($item['on_time_count'] / $item['done_count']) * 100 : 0;

            // Weights: 50% Score (Complexity), 30% Timeliness, 20% Count
            $finalScore = (0.50 * $normScore) + (0.30 * $onTimeRate) + (0.20 * $normCount);

            $item['on_time_rate'] = round($onTimeRate, 1);
            $item['final_score'] = round($finalScore, 1);

            // Grade Badge
            if ($finalScore >= 85) {
                $item['grade'] = 'Elite';
                $item['grade_class'] = 'grade-elite';
            } elseif ($finalScore >= 70) {
                $item['grade'] = 'High Performer';
                $item['grade_class'] = 'grade-high';
            } elseif ($finalScore >= 50) {
                $item['grade'] = 'Standard';
                $item['grade_class'] = 'grade-standard';
            } else {
                $item['grade'] = 'Needs Improvement';
                $item['grade_class'] = 'grade-low';
            }
        }
        unset($item);

        // 6. Sort descending by final_score
        uasort($rankingData, function ($a, $b) {
            return $b['final_score'] <=> $a['final_score'];
        });

        // Add Rank position (1, 2, 3...)
        $rank = 1;
        foreach ($rankingData as &$item) {
            $item['rank'] = $rank++;
        }
        unset($item);

        return [
            'users' => $rankingData,
            'from_timestamp' => $fromTimestamp,
            'to_timestamp' => $toTimestamp,
        ];
    }

}
