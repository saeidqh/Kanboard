<?php

namespace Kanboard\Plugin\AccessGuard\Policy;

class AccessPolicy
{
    // تعریف صریح سطوح و دسترسی‌ها
    // نقش‌های اختصاصی پروژه: Expert, Manager, CEO, Admin
    public static function getPermissions($roleName)
    {
        $roleName = strtolower(trim($roleName));

        $matrix = [
            // ۱. نقش کارشناس: فوق‌العاده محدود
            'expert' => [
                'can_create_task'     => false,
                'can_edit_task'       => 'assigned_only', // فقط تسکی که به خودش اساین شده
                'can_view_analytics'  => false,
                'can_manage_project'  => false,
                'can_create_project'  => false,
                'task_scope'          => 'assigned_or_created', // فقط کارهای خودش
                'hide_all_menus'      => true // منوها کاملاً پاکسازی شود
            ],

            // ۲. نقش مدیر: ساخت و ارجاع تسک + دیدن گزارش فقط افراد تحت سرپرستی
            'manager' => [
                'can_create_task'     => true,
                'can_edit_task'       => true,
                'can_view_analytics'  => 'team_only', // فقط کارشناسانی که بهشون تسک داده
                'can_manage_project'  => false,
                'can_create_project'  => true,
                'task_scope'          => 'assigned_or_created',
                'hide_all_menus'      => false
            ],

            // ۳. نقش مدیرعامل: دسترسی مشاهده و بیزینسی کامل به پروژه‌ها بدون دسترسی به تنظیمات فنی هاست
            'ceo' => [
                'can_create_task'     => true,
                'can_edit_task'       => true,
                'can_view_analytics'  => 'all',
                'can_manage_project'  => true,
                'can_create_project'  => true,
                'task_scope'          => 'all',
                'hide_all_menus'      => false
            ],

            // ۴. مدیر سیستم / ادمین کل
            'admin' => [
                'can_create_task'     => true,
                'can_edit_task'       => true,
                'can_view_analytics'  => 'all',
                'can_manage_project'  => true,
                'can_create_project'  => true,
                'task_scope'          => 'all',
                'hide_all_menus'      => false
            ],
        ];

        return $matrix[$roleName] ?? $matrix['expert'];
    }

    /**
     * پیدا کردن شناسه کاربران زیرمجموعه یک مدیر (کارشناسانی که مدیر به آن‌ها تسک داده است)
     */
    public static function getManagerSubordinateIds($db, $managerId, $projectId)
    {
        // کوئری مستقیم: کاربرانی که تسکی دارند که خالق آن (creator_id) این مدیر است
        return $db->table('tasks')
            ->eq('project_id', $projectId)
            ->eq('creator_id', $managerId)
            ->neq('owner_id', 0)
            ->distinct()
            ->findAllByColumn('owner_id');
    }
}
