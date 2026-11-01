<?php
use Kanboard\Plugin\AccessGuard\Policy\AccessPolicy;

// ۱. دریافت اطلاعات کاربر از طریق Helper
$userRole = $this->helper->user->getRole();
$userId = $this->helper->user->getId();
$currentProjectRole = '';

// ۲. بررسی نقش پروژه
if (isset($project['id'])) {
    if (isset($this->projectUserRoleModel)) {
        $currentProjectRole = $this->projectUserRoleModel->getUserRole($project['id'], $userId);
    }
}

// ۳. دریافت قوانین بر اساس نقش
$policy = AccessPolicy::getPermissions($currentProjectRole ?: $userRole);

// ۴. اعمال استایل فقط برای Expert
if ($policy['hide_all_menus'] && $userRole !== 'app-admin') {
    echo '<link rel="stylesheet" href="'.$this->url->dir().'plugins/AccessGuard/Template/guard/clean_expert.css">';
    
    // ۵. اسکریپت اختصاصی برای اجبار فیلتر پیش‌فرض (تسک‌های ارجاع‌شده + ایجادشده)
    ?>
    <script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {
        // فیلتر پیش‌فرض مورد نظر برای Expert
        var defaultFilter = "status:open (assignee:me OR creator:me)";

        // پیدا کردن باکس جستجو در صفحه برد
        var searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            // اگر فیلتر خالی بود یا فقط status:open بود، فیلتر ترکیبی را اعمال کن
            if (searchInput.value === '' || searchInput.value === 'status:open' || searchInput.value.indexOf('assignee:me') !== -1) {
                if (searchInput.value !== defaultFilter) {
                    searchInput.value = defaultFilter;
                    
                    // اگر فرم جستجو وجود دارد، می‌توانید آن را به صورت خودکار سابمیت کنید تا برد فیلتر شود
                    // var form = searchInput.closest('form');
                    // if (form) { form.submit(); }
                }
            }
        }
    });
    </script>
    <?php
}
?>
