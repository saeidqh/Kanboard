<div class="page-header">
    <h2><?= $this->text->e($title) ?></h2>
</div>

<div class="sunburst-dashboard-vertical">

    <!-- Sunburst Chart Card (Extra Large & Clear) -->
    <div class="sunburst-chart-container-card">
        <div id="chart-loading" class="chart-loading-box">
            <i class="fa fa-spinner fa-spin fa-3x"></i>
            <p><?= t('Rendering Sunburst Hierarchy...') ?></p>
        </div>

        <div id="sunburst_chart" style="width: 100%; height: 850px;"></div>
    </div>

    <!-- Workload Table Card (Below Chart) -->
    <div class="sunburst-table-container-card">
        <?= $this->render('SunburstAnalytics:analytic/workload_table', [
            'workloadData' => $workloadData,
            'project'      => $project,
        ]) ?>
    </div>
</div>

<!-- JSON Data Holder -->
<script id="sunburst-data-holder" type="application/json">
    <?= $chartData ?>
</script>

<!-- Assets -->
<?= $this->asset->css('plugins/SunburstAnalytics/assets/css/sunburst_workload.css') ?>
<?= $this->asset->js('plugins/SunburstAnalytics/assets/js/plotly.min.js') ?>
<?= $this->asset->js('plugins/SunburstAnalytics/assets/js/sunburst.js') ?>
