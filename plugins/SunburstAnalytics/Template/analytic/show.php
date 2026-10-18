<div class="page-header">
    <h2><?= t('Sunburst Interactive Workload & Progress') ?> (داشبورد خورشیدی پیشرفت پروژه)</h2>
</div>

<!-- راهنمای مدیریتی رنگ‌ها و تعامل -->
<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 15px; font-family: Tahoma, sans-serif; font-size: 13px;">
    <div>
        <span style="font-weight: bold; color: #1e293b;">راهنمای تعاملی:</span>
        <span style="color: #64748b;">موس را روی هر بخش ببرید تا آمار تسک‌ها را ببینید. با کلیک روی هر بخش زوم کنید و با کلیک روی تسک‌های بیرونی، مستقیم وارد کارت تسک شوید.</span>
    </div>
    <div style="display: flex; gap: 14px; align-items: center; font-size: 12px;">
        <span style="font-weight: bold; color: #334155;">راهنمای وضعیت و رنگ‌ها:</span>
        <span><span style="display:inline-block;width:12px;height:12px;background:#7f8c8d;border-radius:3px;vertical-align:middle;margin-left:4px;"></span>Backlog</span>
        <span><span style="display:inline-block;width:12px;height:12px;background:#2980b9;border-radius:3px;vertical-align:middle;margin-left:4px;"></span>Ready</span>
        <span><span style="display:inline-block;width:12px;height:12px;background:#e67e22;border-radius:3px;vertical-align:middle;margin-left:4px;"></span>Working (WIP)</span>
        <span><span style="display:inline-block;width:12px;height:12px;background:#27ae60;border-radius:3px;vertical-align:middle;margin-left:4px;"></span>Done (انجام‌شده)</span>
    </div>
</div>

<!-- کانتینر نمودار خورشیدی -->
<div id="sunburst_chart" style="width: 100%; min-height: 750px; direction: ltr;"></div>

<!-- داده‌های تزریق‌شده از سمت سرور به جاوااسکریپت -->
<script type="application/json" id="sunburst-data-holder">
<?= $chartData ?>
</script>

<!-- لود مطمئن اسکریپت‌ها -->
<script type="text/javascript" src="plugins/SunburstAnalytics/Asset/plotly.min.js"></script>
<script type="text/javascript" src="plugins/SunburstAnalytics/Asset/sunburst.js"></script>
