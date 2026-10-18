<div class="page-header">
    <h2>☀️ <?= t('Sunburst Interactive Workload & Progress') ?> (نمودار خورشیدی پیشرفت و بار کاری)</h2>
</div>

<div style="background: #ffffff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 25px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
        <div>
            <span style="font-size: 15px; font-weight: bold; color: #2c3e50;">راهنمای تعاملی:</span>
            <span style="font-size: 13px; color: #7f8c8d; margin-right: 10px;">
                روی هر <b>کاربر</b> یا <b>ستون</b> کلیک کنید تا وارد جزئیات شوید. با کلیک روی <b>تسک نهایی</b>، صفحه تسک در برگه جدید باز می‌شود!
            </span>
        </div>
        <div style="font-size: 12px; color: #555;">
            طیف رنگ بیرونی: 
            <span style="display:inline-block; width:12px; height:12px; background:#e74c3c; border-radius:3px;"></span> ۰٪ (شروع‌نشده) ➔ 
            <span style="display:inline-block; width:12px; height:12px; background:#f1c40f; border-radius:3px;"></span> ۵۰٪ (در جریان) ➔ 
            <span style="display:inline-block; width:12px; height:12px; background:#2ecc71; border-radius:3px;"></span> ۱۰۰٪ (تکمیل)
        </div>
    </div>

    <!-- کانتینر نمودار خورشیدی -->
    <div id="sunburst-chart-container" data-project-id="<?= $project['id'] ?>" style="width: 100%; min-height: 720px;">
        <div id="chart-loading-msg" style="text-align: center; padding: 50px; color: #7f8c8d; font-size: 14px;">
            در حال بارگذاری نمودار خورشیدی...
        </div>
    </div>
</div>

<!-- کپسوله‌سازی امن داده‌های JSON بدون فعال‌سازی خطای CSP -->
<script id="sunburst-data-holder" type="application/json">
<?= $chartData ?>
</script>

<!-- فراخوانی اسکریپت‌های اکسترنال محلی منطبق بر استاندارد Content Security Policy -->
<script src="plugins/SunburstAnalytics/Asset/plotly.min.js"></script>
<script src="plugins/SunburstAnalytics/Asset/sunburst.js"></script>
