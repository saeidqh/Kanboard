# Sunburst + Hierarchy Scope (حیاتی)

## قانون داده

| نقش کاربر لاگین‌شده | چه داده‌ای در نمودار می‌بیند |
|---------------------|-------------------------------|
| Admin سیستم | کل پروژه |
| Role با **level = 1** | کل پروژه (همهٔ افراد دارای نقش در درخت) |
| Role با level ≥ 2 | فقط **خودش + فرزندان** در درخت سازمانی |
| بدون نقش سلسله‌مراتبی | فقط خودش |

فیلتر روی **owner_id** تسک (Assignee) اعمال می‌شود.

---

## ۱) فایل را باز کن

```
plugins/SunburstAnalytics/Service/WorkloadAnalysisService.php
```
(اگر مسیر Service فرق دارد، همان کلاسی که تسک‌ها را برای Sunburst جمع می‌کند.)

---

## ۲) بالای کلاس این use را اضافه کن

```php
use Kanboard\Plugin\HierarchicalRoles\Helper\HierarchicalRoleHelper;
```

---

## ۳) متد کمکی داخل همان کلاس

```php
/**
 * User IDs allowed in analytics for current session.
 * null = full project (Level 1 / admin)
 */
protected function getScopeUserIds($projectId)
{
    if (!class_exists(HierarchicalRoleHelper::class)) {
        return null; // plugin not loaded → no filter
    }
    $helper = new HierarchicalRoleHelper($this->container);
    return $helper->getAnalyticsVisibleUserIds($projectId);
}
```

---

## ۴) جایی که لیست تسک‌ها را می‌گیری (مهم‌ترین نقطه)

### حالت A – با Query Builder کان‌بورد

قبل از `findAll()` / `getQuery()`:

```php
$scopeIds = $this->getScopeUserIds($projectId);

if ($scopeIds !== null) {
    if (empty($scopeIds)) {
        return array(); // هیچ‌کس در محدوده نیست
    }
    $query->in(TaskModel::TABLE.'.owner_id', $scopeIds);
}
```

### حالت B – آرایهٔ تسک بعد از fetch

```php
$tasks = ...; // findAll

if (class_exists(HierarchicalRoleHelper::class)) {
    $helper = new HierarchicalRoleHelper($this->container);
    $tasks = $helper->filterTasksByAnalyticsScope($projectId, $tasks);
}
```

### حالت C – SQL خام

```php
$scopeIds = $this->getScopeUserIds($projectId);
if ($scopeIds !== null) {
    if (empty($scopeIds)) {
        // return empty chart data
    } else {
        $in = implode(',', array_map('intval', $scopeIds));
        $sql .= " AND tasks.owner_id IN ($in) ";
    }
}
```

---

## ۵) اگر Sunburst بر اساس user حلقه می‌زند

بعد از ساختن لیست کاربران:

```php
$scopeIds = $this->getScopeUserIds($projectId);
if ($scopeIds !== null) {
    $users = array_filter($users, function ($u) use ($scopeIds) {
        $id = is_array($u) ? (int) $u['id'] : (int) $u;
        return in_array($id, $scopeIds, true);
    });
}
```

---

## ۶) smart_progress (AutoProgressRollup)

اگر درصد از metadata خوانده می‌شود، **اول تسک را از نظر owner فیلتر کن**، بعد progress را بخوان — وگرنه اعداد بقیه در نمودار می‌آید.

---

## ۷) تست

1. کاربر Level 1 + تیک Analytics → Sunburst مثل قبل (کل پروژه).
2. کاربر Level 2 (مثلاً hsse) + تیک Analytics → فقط تسک‌های assignee شده به hsse و زیرمجموعه‌ها (exp hsse، senior، …).
3. همان کاربر Level 2 نباید تسک‌های شاخهٔ دیگر را ببیند.

---

## API آماده‌شده در HierarchicalRoles

```php
$h = $this->helper->hierarchicalRoleHelper;
// یا: new HierarchicalRoleHelper($this->container);

$h->seesFullProjectAnalytics($projectId);     // bool
$h->getAnalyticsVisibleUserIds($projectId); // null | int[]
$h->filterTasksByAnalyticsScope($projectId, $tasks);
```
