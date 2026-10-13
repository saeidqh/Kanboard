<?php

namespace Kanboard\Plugin\AutoProgressRollup;

use Kanboard\Core\Plugin\Base;

class Plugin extends Base
{
    // متغیر کمکی برای جلوگیری از حلقه تکرار بی‌نهایت (Infinite Loop Prevention)
    private static $isProcessing = false;

    public function initialize()
    {
        // گوش دادن به رویداد ویرایش و آپدیت تسک
        $this->container['dispatcher']->addListener('task.update', function ($event) {
            $taskId = isset($event['task_id']) ? $event['task_id'] : (isset($event['id']) ? $event['id'] : null);
            if ($taskId) {
                $this->recalculateParentProgress($taskId);
            }
        });

        // گوش دادن به رویداد ثبت یا آپدیت زمان در ساب‌تسک‌ها
        $this->container['dispatcher']->addListener('subtask.time_tracking.update', function ($event) {
            if (isset($event['task_id'])) {
                $this->recalculateParentProgress($event['task_id']);
            }
        });
    }

    private function recalculateParentProgress($taskId)
    {
        // اگر سیستم در حال آپدیت تسک والد بود، متوقف شو تا در لوپ نیفتد
        if (self::$isProcessing) {
            return;
        }

        // متد رسمی و استاندارد کان‌بورد برای خواندن لینک‌ها
        $links = $this->taskLinkModel->getAll($taskId);
        if (empty($links)) {
            return;
        }

        foreach ($links as $link) {
            // بررسی این‌که آیا این تسک فرزند یک والد است (is a child of)
            if (isset($link['label']) && strtolower($link['label']) === 'is a child of') {
                $parentId = $link['task_id'];

                // پیدا کردن تمامی لینک‌های متصل به تسک والد
                $parentLinks = $this->taskLinkModel->getAll($parentId);
                $totalEstimated = 0;
                $totalSpent = 0;

                foreach ($parentLinks as $pLink) {
                    // جمع زدن ساعت‌های همه تسک‌هایی که فرزند این والد هستند
                    if (isset($pLink['label']) && strtolower($pLink['label']) === 'is a parent of') {
                        $childTask = $this->taskFinderModel->getById($pLink['task_id']);
                        if ($childTask) {
                            $totalEstimated += floatval($childTask['time_estimated']);
                            $totalSpent += floatval($childTask['time_spent']);
                        }
                    }
                }

                // فعال کردن قفل برای جلوگیری از اجرای مجدد رویداد
                self::$isProcessing = true;

                // ذخیره مجموع زمان‌ها روی تسک والد (Parent)
                $this->taskModificationModel->update([
                    'id' => $parentId,
                    'time_estimated' => $totalEstimated,
                    'time_spent' => $totalSpent,
                ]);

                // آزاد کردن قفل
                self::$isProcessing = false;
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
        return '1.0.1';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/kanboard/kanboard';
    }
}
