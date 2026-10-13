<?php

namespace Kanboard\Plugin\AutoProgressRollup;

use Kanboard\Core\Plugin\Base;
use Kanboard\Model\TaskLinkModel;

class Plugin extends Base
{
    public function initialize()
    {
        // رویداد: هنگام ویرایش یا بروزرسانی تسک
        $this->container['dispatcher']->addListener('task.update', function ($event) {
            $this->recalculateParentProgress($event['task_id']);
        });

        // رویداد: هنگام ثبت زمان یا ویرایش آن در تسک
        $this->container['dispatcher']->addListener('subtask.time_tracking.update', function ($event) {
            if (isset($event['task_id'])) {
                $this->recalculateParentProgress($event['task_id']);
            }
        });
    }

    private function recalculateParentProgress($taskId)
    {
        // پیدا کردن لینک‌های ارتباطی این تسک
        $links = $this->taskLinkModel->getAllGroupedByLink($taskId);

        // بررسی اینکه آیا این تسک فرزندی برای یک والد است (رابطه "is a child of")
        if (!empty($links) && isset($links['is a child of'])) {
            foreach ($links['is a child of'] as $parentRelation) {
                $parentId = $parentRelation['task_id'];

                // پیدا کردن تمام فرزندان این والد
                $parentLinks = $this->taskLinkModel->getAllGroupedByLink($parentId);
                
                $totalEstimated = 0;
                $totalSpent = 0;

                if (isset($parentLinks['is a parent of'])) {
                    foreach ($parentLinks['is a parent of'] as $childRelation) {
                        $childTask = $this->taskFinderModel->getById($childRelation['task_id']);
                        if ($childTask) {
                            $totalEstimated += floatval($childTask['time_estimated']);
                            $totalSpent += floatval($childTask['time_spent']);
                        }
                    }
                }

                // بروزرسانی زمان‌های تخمینی و صرف‌شده در تسک والد
                $this->taskModificationModel->update([
                    'id' => $parentId,
                    'time_estimated' => $totalEstimated,
                    'time_spent' => $totalSpent,
                ]);
            }
        }
    }

    public function getPluginName()
    {
        return 'Auto Progress Rollup';
    }

    public function getPluginDescription()
    {
        return 'Calculates and rolls up total estimated and spent time from child tasks to parent task automatically.';
    }

    public function getPluginAuthor()
    {
        return 'Ahvaz005 Team';
    }

    public function getPluginVersion()
    {
        return '1.0.0';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/kanboard/kanboard';
    }
}
