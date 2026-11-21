<?php
// دریافت نقش و وضعیت کاربر جاری
$isAppAdmin = $this->user->isAdmin();
$userId = $this->user->getId();
$projectId = $this->app->request->getIntegerParam('project_id');

// اگر کاربر ادمین است، اصلاً هیچ استایل محدودیتی اعمال نشود
if ($isAppAdmin) {
    return;
}

$isExpert = false;

if ($projectId > 0) {
    $projectRole = $this->app->projectUserRoleModel->getUserRole($projectId, $userId);
    // اگر نقش درون پروژه Expert بود
    if ($projectRole === 'Expert') {
        $isExpert = true;
    }
} else {
    // برای صفحاتی مثل داشبورد (که project_id ندارند):
    // اگر کاربر در پروژه‌هایش نقش اکسپرت دارد و ادمین نیست:
    $isExpert = true; 
}
?>

<?php if ($isExpert): ?>
<style>
/* ==========================================================
   AccessGuard Plugin - Expert Role Custom CSS
   این استایل‌ها فقط برای کاربر غیر ادمین و نقش Expert رندر می‌شوند
   ========================================================== */

/* ۱. مخفی‌سازی فیلترها و فرم جستجو */
.board-search,
#board-search,
form[method="get"],
.input-addon,
.input-addon-field,
input[name="search"],
.fa-filter,
.fa-user,
.fa-folder,
.fa-calendar {
    display: none !important;
}

.project-header .dropdown-component > .dropdown > a.action-menu[title="Configure this project"] {
    display: none !important;
}

/* ۲. پروفایل کاربر در هدر */
header .dropdown > a.dropdown-menu-link-icon {
    display: inline-flex !important;
}

header .dropdown > .dropdown-menu {
    display: none !important;
}

.dropdown-submenu-open li:has(a[href*="dashboard"]),
.dropdown-submenu-open li:has(a[href*="/projects"]),
.dropdown-submenu-open li:has(a[href*="docs.kanboard.org"]),
.dropdown-submenu-open li:has(a[href*="/user/show/"]) {
    display: none !important;
}

/* غیرفعال کردن کلیک و حذف فلش شماره تسک */
.table-list-row .dropdown > a.dropdown-menu-link-icon.dropdown-menu i.fa-caret-down {
    display: none !important;
}

.table-list-row .dropdown > a.dropdown-menu-link-icon.dropdown-menu {
    pointer-events: none !important;
    cursor: default !important;
    color: inherit !important;
}

.task-board-header .dropdown-menu-link-icon {
    pointer-events: none !important;
}

.table-striped a[href*="/user/show/"] {
    pointer-events: none !important;
    cursor: default !important;
    color: inherit !important;
    text-decoration: none !important;
}

.task-summary-column a[href*="/board/"] {
    display: none !important;
}

/* ساب‌تسک‌ها */
label[for="form-user_id"],
select#form-user_id {
    display: none !important;
}

table.subtasks-table thead {
    display: table-header-group !important;
}

table.subtasks-table tr {
    display: table-row !important;
}

table.subtasks-table th {
    display: table-cell !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.sidebar li:has(a[href*="/position/show"]),
.sidebar a[href*="/position/show"] {
    display: none !important;
}

.sidebar ul li a[href*="/user/password"],
.sidebar ul li:has(a[href*="/user/password"]) {
    display: none !important;
}

/* دکمه‌های مدیریت و ادیت */
.board-header .board-header-btn,
a[href*="ProjectViewController"],
a[href*="analytic"],
a[href*="gantt"],
a[href*="project/create"],
a[href*="TaskCreationController"],
.board-add-icon {
    display: none !important;
}

.subtasks-table .dropdown > a.dropdown-menu-link-icon {
    display: none !important;
}

#main .views-switcher-component a.view-gantt,
.views li:has(a.view-gantt) {
    display: none !important;
}

a.js-subtask-toggle-status {
    display: none !important;
}

/* تب‌های بالای پروژه */
.activity-header ul.nav.nav-tabs li a[href*="OverviewController"],
.activity-header ul.nav.nav-tabs li a[href*="GanttController"],
.activity-header ul.nav.nav-tabs li a[href*="Documentation"],
.activity-header ul.nav.nav-tabs li a[href*="analytic"],
.activity-header ul.nav.nav-tabs li a[href*="calendar"],
.activity-header ul.nav.nav-tabs li:has(a[href*="OverviewController"]),
.activity-header ul.nav.nav-tabs li:has(a[href*="GanttController"]),
.activity-header ul.nav.nav-tabs li:has(a[href*="Documentation"]) {
    display: none !important;
}

/* تب‌ها در تسک */
.views li:has(a.view-overview),
.views li:has(a.view-list),
a.view-overview,
a.view-list {
    display: none !important;
}

.views li a.view-board {
    display: inline-block !important;
}

.task-board-header a[href*="edit"],
.task-board-header .fa-edit {
    display: none !important;
}

.sidebar #dashboard a[href*="dashboard"],
.sidebar li:has(a[href*="dashboard"]) {
    display: none !important;
}

.page-header a[href*="projects"] {
    display: none !important;
}

.page-header a[href*="my-activity"] {
    display: none !important;
}

.table-list-row .table-list-details {
    display: none !important;
}
</style>
<?php endif; ?>
