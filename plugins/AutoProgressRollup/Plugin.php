<?php

namespace Kanboard\Plugin\AutoProgressRollup;

use Kanboard\Core\Plugin\Base;

class Plugin extends Base
{
    /**
     * جلوگیری از حلقه بی‌نهایت هنگام آپدیت تسک
     *
     * @var bool
     */
    private static $isProcessing = false;

    /**
     * کلیدهای ذخیره‌شده در Task Metadata
     */
    const META_PROGRESS = 'smart_progress';
    const META_SOURCE   = 'smart_progress_source';

    /**
     * وضعیت‌های استاندارد Subtask در Kanboard
     */
    const SUBTASK_TODO        = 0;
    const SUBTASK_IN_PROGRESS = 1;
    const SUBTASK_DONE        = 2;

public function initialize()
{
    // رویدادهای مربوط به خود تسک
    $taskEvents = [
        'task.create',
        'task.update',
        'task.move.column',
        'task.close',
        'task.open',
    ];

    foreach ($taskEvents as $eventName) {
        $this->container['dispatcher']->addListener(
            $eventName,
            function ($event) {
                $taskId = $this->extractTaskId($event);
                if ($taskId > 0) {
                    $this->processTaskAndParent($taskId);
                }
            }
        );
    }

    // رویدادهای مربوط به Subtask
    $subtaskEvents = [
        'subtask.create',
        'subtask.update',
        'subtask.delete',
        'subtask.time_tracking.update',
    ];

    foreach ($subtaskEvents as $eventName) {
        $this->container['dispatcher']->addListener(
            $eventName,
            function ($event) {
                $taskId = $this->extractTaskIdFromSubtaskEvent($event);
                if ($taskId > 0) {
                    $this->processTaskAndParent($taskId);
                }
            }
        );
    }

    // نمایش Progress Bar زیر کارت‌های بورد
    $this->template->hook->attach(
        'template:board:task:footer',
        'AutoProgressRollup:task_footer/progress_bar'
    );

    // لینک Recalculate در منوی پروژه (از مرحله ۳)
   /* $this->template->hook->attach(
        'template:project:dropdown',
        'AutoProgressRollup:project_dropdown'
    );
    */
}

    /**
     * محاسبه تسک تغییرکرده و سپس والد آن
     *
     * @param int $taskId
     */
    private function processTaskAndParent($taskId)
    {
        if (self::$isProcessing) {
            return;
        }

        self::$isProcessing = true;

        try {
            // ابتدا زمان‌های والد احتمالی را Rollup می‌کنیم
            $parentIds = $this->findParentIds($taskId);

            foreach ($parentIds as $parentId) {
                $this->rollupParentTimes($parentId);
            }

            // محاسبه پیشرفت خود تسک
            $this->calculateAndSaveProgress($taskId);

            // پس از محاسبه فرزند، پیشرفت والد محاسبه می‌شود
            foreach ($parentIds as $parentId) {
                $this->calculateAndSaveProgress($parentId);
            }
        } finally {
            // حتی اگر Exception رخ دهد، قفل آزاد می‌شود
            self::$isProcessing = false;
        }
    }

    /**
     * محاسبه و ذخیره Progress هوشمند
     *
     * @param int $taskId
     * @return float|null
     */
    private function calculateAndSaveProgress($taskId)
    {
        $task = $this->taskFinderModel->getById($taskId);

        if (empty($task)) {
            return null;
        }

        $result   = $this->calculateSmartProgress($task);
        $progress = $result['progress'];
        $source   = $result['source'];

        // Progress را در Metadata ذخیره می‌کنیم
        // به time_spent و time_estimated دست نمی‌زنیم
        $this->taskMetadataModel->save($taskId, [
            self::META_PROGRESS => (string) $progress,
            self::META_SOURCE   => $source,
        ]);

        return $progress;
    }

    /**
     * موتور اصلی محاسبه Progress
     *
     * اولویت‌ها:
     * 1. تسک بسته‌شده → 100%
     * 2. ستون آخر (Done) → 100%
     * 3. دارای Child Task → میانگین وزنی فرزندان
     * 4. دارای Subtask → نسبت وضعیت ساب‌تسک‌ها داخل بازه ستون
     * 5. دارای زمان تخمینی → نسبت زمان داخل بازه ستون
     * 6. فقط جایگاه ستون
     *
     * @param array $task
     * @return array
     */
    private function calculateSmartProgress(array $task)
    {
        // قانون ۱: تسک بسته‌شده همیشه ۱۰۰٪
        if ((int) $task['is_active'] === 0) {
            return [
                'progress' => 100.0,
                'source'   => 'closed',
            ];
        }

        $columns = $this->getProjectColumns($task['project_id']);

        // قانون ۲: ستون آخر پروژه = Done
        if ($this->isLastColumn($task['column_id'], $columns)) {
            return [
                'progress' => 100.0,
                'source'   => 'done_column',
            ];
        }

        // قانون ۳: اگر Parent دارای Child Task باشد
        $children = $this->getChildTasks($task['id']);

        if (!empty($children)) {
            return [
                'progress' => $this->calculateChildrenProgress($children, $columns),
                'source'   => 'child_tasks',
            ];
        }

        // قانون ۴: اگر Subtask وجود داشته باشد
        $subtasks = $this->getSubtasks($task['id']);

        if (!empty($subtasks)) {
            $executionRatio = $this->calculateSubtaskRatio($subtasks);
            $progress       = $this->fitIntoColumnRange($executionRatio, $task, $columns);

            return [
                'progress' => $this->limitOpenProgress($progress),
                'source'   => 'subtasks',
            ];
        }

        // قانون ۵: اگر زمان تخمینی وجود داشته باشد
        $estimated = (float) $task['time_estimated'];
        $spent     = (float) $task['time_spent'];

        if ($estimated > 0) {
            $timeRatio = min(1.0, max(0.0, $spent / $estimated));
            $progress  = $this->fitIntoColumnRange($timeRatio, $task, $columns);

            return [
                'progress' => $this->limitOpenProgress($progress),
                'source'   => 'time',
            ];
        }

        // قانون ۶: فقط جایگاه ستون
        return [
            'progress' => $this->limitOpenProgress(
                $this->calculateColumnProgress($task['column_id'], $columns)
            ),
            'source'   => 'column',
        ];
    }

    /**
     * محاسبه پیشرفت Parent بر اساس Child Taskها (وزن‌دار)
     *
     * @param array $children
     * @param array $columns
     * @return float
     */
    private function calculateChildrenProgress(array $children, array $columns)
    {
        $weightedProgress = 0.0;
        $totalWeight      = 0.0;

        foreach ($children as $child) {
            $childResult   = $this->calculateSmartProgress($child);
            $childProgress = $childResult['progress'];

            // وزن = زمان تخمینی (اگر صفر بود، وزن ۱)
            $weight = (float) $child['time_estimated'];
            if ($weight <= 0) {
                $weight = 1.0;
            }

            $weightedProgress += $childProgress * $weight;
            $totalWeight      += $weight;

            // Metadata فرزند را هم به‌روز می‌کنیم
            $this->taskMetadataModel->save($child['id'], [
                self::META_PROGRESS => (string) $childProgress,
                self::META_SOURCE   => $childResult['source'],
            ]);
        }

        if ($totalWeight <= 0) {
            return 0.0;
        }

        return $this->limitOpenProgress(round($weightedProgress / $totalWeight, 1));
    }

    /**
     * محاسبه نسبت تکمیل Subtaskها
     * Todo = 0% | In Progress = 50% | Done = 100%
     *
     * @param array $subtasks
     * @return float  عددی بین ۰ و ۱
     */
    private function calculateSubtaskRatio(array $subtasks)
    {
        $points = 0.0;
        $total  = count($subtasks);

        if ($total === 0) {
            return 0.0;
        }

        foreach ($subtasks as $subtask) {
            $status = (int) $subtask['status'];

            if ($status === self::SUBTASK_DONE) {
                $points += 1.0;
            } elseif ($status === self::SUBTASK_IN_PROGRESS) {
                $points += 0.5;
            }
        }

        return min(1.0, max(0.0, $points / $total));
    }

    /**
     * تبدیل پیشرفت اجرایی به بازه مجاز ستون
     *
     * @param float $executionRatio
     * @param array $task
     * @param array $columns
     * @return float
     */
    private function fitIntoColumnRange($executionRatio, array $task, array $columns)
    {
        $range   = $this->getColumnProgressRange($task['column_id'], $columns);
        $minimum = $range['min'];
        $maximum = $range['max'];

        $progress = $minimum + ($executionRatio * ($maximum - $minimum));

        return round($progress, 1);
    }

    /**
     * مشخص کردن بازه Progress هر ستون
     * (با پشتیبانی از نام‌های رایج + Fallback هوشمند)
     *
     * @param int   $columnId
     * @param array $columns
     * @return array
     */
    private function getColumnProgressRange($columnId, array $columns)
    {
        $columnTitle = '';

        foreach ($columns as $column) {
            if ((int) $column['id'] === (int) $columnId) {
                $columnTitle = strtolower(trim($column['title']));
                break;
            }
        }

        // تنظیم مخصوص Workflow پروژه شما
        if (strpos($columnTitle, 'backlog') !== false) {
            return ['min' => 0, 'max' => 10];
        }

        if (strpos($columnTitle, 'ready') !== false) {
            return ['min' => 10, 'max' => 25];
        }

        if (
            strpos($columnTitle, 'work in progress') !== false ||
            strpos($columnTitle, 'wip') !== false ||
            strpos($columnTitle, 'in progress') !== false
        ) {
            return ['min' => 25, 'max' => 75];
        }

        if (
            strpos($columnTitle, 'pause') !== false ||
            strpos($columnTitle, 'hold') !== false ||
            strpos($columnTitle, 'blocked') !== false
        ) {
            return ['min' => 25, 'max' => 75];
        }

        if (
            strpos($columnTitle, 'review') !== false ||
            strpos($columnTitle, 'quality') !== false ||
            strpos($columnTitle, 'qc') !== false ||
            strpos($columnTitle, 'test') !== false
        ) {
            return ['min' => 75, 'max' => 99];
        }

        if (
            strpos($columnTitle, 'done') !== false ||
            strpos($columnTitle, 'complete') !== false ||
            strpos($columnTitle, 'finished') !== false
        ) {
            return ['min' => 100, 'max' => 100];
        }

        // Fallback هوشمند بر اساس جایگاه ستون
        $baseProgress = $this->calculateColumnProgress($columnId, $columns);

        return [
            'min' => max(0, $baseProgress - 12),
            'max' => min(99, $baseProgress + 12),
        ];
    }

    /**
     * Progress پایه بر اساس جایگاه ستون
     *
     * @param int   $columnId
     * @param array $columns
     * @return float
     */
    private function calculateColumnProgress($columnId, array $columns)
    {
        $count = count($columns);

        if ($count <= 1) {
            return 0.0;
        }

        foreach ($columns as $index => $column) {
            if ((int) $column['id'] === (int) $columnId) {
                return round(($index * 100) / ($count - 1), 1);
            }
        }

        return 0.0;
    }

    /**
     * تسک باز نباید قبل از Done به ۱۰۰ برسد
     *
     * @param float $progress
     * @return float
     */
    private function limitOpenProgress($progress)
    {
        return round(min(99.0, max(0.0, $progress)), 1);
    }

    /**
     * Rollup کردن زمان فرزندان روی والد
     *
     * @param int $parentId
     */
    private function rollupParentTimes($parentId)
    {
        $children = $this->getChildTasks($parentId);

        if (empty($children)) {
            return;
        }

        $totalEstimated = 0.0;
        $totalSpent     = 0.0;

        foreach ($children as $child) {
            $totalEstimated += (float) $child['time_estimated'];
            $totalSpent     += (float) $child['time_spent'];
        }

        $this->taskModificationModel->update([
            'id'             => $parentId,
            'time_estimated' => round($totalEstimated, 2),
            'time_spent'     => round($totalSpent, 2),
        ]);
    }

    /**
     * دریافت فرزندان یک Parent Task
     *
     * @param int $parentId
     * @return array
     */
    private function getChildTasks($parentId)
    {
        $children = [];
        $links    = $this->taskLinkModel->getAll($parentId);

        foreach ($links as $link) {
            if (
                isset($link['label'], $link['task_id']) &&
                strtolower(trim($link['label'])) === 'is a parent of'
            ) {
                $child = $this->taskFinderModel->getById($link['task_id']);
                if (!empty($child)) {
                    $children[] = $child;
                }
            }
        }

        return $children;
    }

    /**
     * پیدا کردن والدهای یک Child Task
     *
     * @param int $taskId
     * @return array
     */
    private function findParentIds($taskId)
    {
        $parentIds = [];
        $links     = $this->taskLinkModel->getAll($taskId);

        foreach ($links as $link) {
            if (
                isset($link['label'], $link['task_id']) &&
                strtolower(trim($link['label'])) === 'is a child of'
            ) {
                $parentIds[] = (int) $link['task_id'];
            }
        }

        return array_values(array_unique($parentIds));
    }

    /**
     * خواندن Subtaskها
     *
     * @param int $taskId
     * @return array
     */
    private function getSubtasks($taskId)
    {
        return $this->db
            ->table('subtasks')
            ->eq('task_id', $taskId)
            ->asc('position')
            ->findAll();
    }

    /**
     * ستون‌های فعال پروژه
     *
     * @param int $projectId
     * @return array
     */
    private function getProjectColumns($projectId)
    {
        return $this->db
            ->table('columns')
            ->eq('project_id', $projectId)
            ->eq('hide_in_dashboard', 0)
            ->asc('position')
            ->findAll();
    }

    /**
     * آیا تسک در آخرین ستون است؟
     *
     * @param int   $columnId
     * @param array $columns
     * @return bool
     */
    private function isLastColumn($columnId, array $columns)
    {
        if (empty($columns)) {
            return false;
        }

        $lastColumn = end($columns);

        return isset($lastColumn['id']) &&
            (int) $lastColumn['id'] === (int) $columnId;
    }

    /**
     * استخراج task_id از رویدادهای Task
     *
     * @param mixed $event
     * @return int
     */
    private function extractTaskId($event)
    {
        if (is_array($event)) {
            if (isset($event['task_id'])) {
                return (int) $event['task_id'];
            }
            if (isset($event['id'])) {
                return (int) $event['id'];
            }
            if (isset($event['task']['id'])) {
                return (int) $event['task']['id'];
            }
        }

        return 0;
    }

    /**
     * استخراج task_id از رویدادهای Subtask
     *
     * @param mixed $event
     * @return int
     */
    private function extractTaskIdFromSubtaskEvent($event)
    {
        if (is_array($event)) {
            if (isset($event['task_id'])) {
                return (int) $event['task_id'];
            }
            if (isset($event['subtask']['task_id'])) {
                return (int) $event['subtask']['task_id'];
            }

            // بعضی رویدادها فقط ID زیرتسک را می‌فرستند
            $subtaskId = 0;
            if (isset($event['subtask_id'])) {
                $subtaskId = (int) $event['subtask_id'];
            } elseif (isset($event['id'])) {
                $subtaskId = (int) $event['id'];
            }

            if ($subtaskId > 0) {
                $subtask = $this->db
                    ->table('subtasks')
                    ->eq('id', $subtaskId)
                    ->findOne();

                if (!empty($subtask['task_id'])) {
                    return (int) $subtask['task_id'];
                }
            }
        }

        return 0;
    }

    public function getPluginName()
    {
        return 'Smart Auto Progress Rollup';
    }

    public function getPluginDescription()
    {
        return 'Calculates smart progress using Done status, child tasks, subtasks, time tracking and workflow columns. Stores result in task metadata.';
    }

    public function getPluginAuthor()
    {
        return 'Ahvaz005 Team';
    }

    public function getPluginVersion()
    {
        return '2.1.0';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/saeidqh/Kanboard';
    }
}