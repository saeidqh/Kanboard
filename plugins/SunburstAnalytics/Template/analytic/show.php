<div class="page-header">
    <h2><?= t('Sunburst Interactive Workload & Progress') ?> (داشبورد لوکس Apache ECharts - اهواز 005)</h2>
</div>

<!-- راهنمای بصری لایه‌ها و رنگ‌ها -->
<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 15px; font-family: Tahoma, sans-serif; font-size: 13px;">
    <div>
        <span style="font-weight: bold; color: #1e293b;">راهنمای تعاملی ۳۶۰ درجه ECharts:</span>
        <span style="color: #64748b;">برای زوم روی هر بخش کلیک کنید (با کلیک وسط به مرحله قبل برگردید) و برای باز شدن کارت تسک روی بیرونی‌ترین لایه کلیک کنید.</span>
    </div>
    <div style="display: flex; gap: 14px; align-items: center; font-size: 12px;">
        <span style="font-weight: bold; color: #334155;">رنگ‌بندی ستون‌ها:</span>
        <span><span style="display:inline-block;width:12px;height:12px;background:#7f8c8d;border-radius:3px;vertical-align:middle;margin-left:4px;"></span>Backlog</span>
        <span><span style="display:inline-block;width:12px;height:12px;background:#2980b9;border-radius:3px;vertical-align:middle;margin-left:4px;"></span>Ready</span>
        <span><span style="display:inline-block;width:12px;height:12px;background:#e67e22;border-radius:3px;vertical-align:middle;margin-left:4px;"></span>Working</span>
        <span><span style="display:inline-block;width:12px;height:12px;background:#27ae60;border-radius:3px;vertical-align:middle;margin-left:4px;"></span>Done</span>
    </div>
</div>

<!-- کانتینر چارت خورشیدی -->
<div id="echarts_sunburst" style="width: 100%; height: 780px;"></div>

<!-- داده‌های سمت سرور -->
<script type="application/json" id="sunburst-data-holder">
<?= $chartData ?>
</script>

<!-- لود کاملاً آفلاین و لوکال کتابخانه‌ها از پوشه Asset پلاگین -->
<script type="text/javascript" src="plugins/SunburstAnalytics/Asset/echarts.min.js"></script>
<script type="text/javascript" src="plugins/SunburstAnalytics/Asset/sunburst.js"></script>
