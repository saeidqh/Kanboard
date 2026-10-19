<div class="page-header" style="text-align: left; direction: ltr;">
    <h2><?= $this->text->e($title) ?></h2>
</div>

<div class="sunburst-analytics-wrapper" style="text-align: left; direction: ltr;">
    <!-- Navigation Tabs -->
<div class="analytics-tabs-nav">
    <button type="button" class="tab-btn active" data-tab="workload">
        <i class="fa fa-pie-chart" aria-hidden="true"></i> Workload & Sunburst
    </button>
    <button type="button" class="tab-btn" data-tab="ranking">
        <i class="fa fa-trophy" aria-hidden="true"></i> Performance Ranking
    </button>
</div>

    <!-- Tab 1: Workload & Sunburst -->
    <div id="tab-workload" class="tab-content active">
        <div class="sunburst-chart-box">
            <!-- Loading Indicator -->
            <div id="chart-loading" class="chart-loading-overlay">
                <div class="spinner"></div>
                <p>Generating Sunburst Workload Chart...</p>
            </div>
            
            <!-- Sunburst Chart Container (ID matched with sunburst.js) -->
            <div id="sunburst_chart" style="width: 100%; height: 850px;"></div>
        </div>

        <!-- Chart JSON Data Holder (Required by sunburst.js) -->
        <script id="sunburst-data-holder" type="application/json">
            <?= $chartData ?>
        </script>

        <!-- Workload Table Partial -->
        <div class="sunburst-table-box" style="margin-top: 30px;">
            <?= $this->render('SunburstAnalytics:analytic/workload_table', [
                'workloadData' => $workloadData,
                'project'      => $project,
            ]) ?>
        </div>
    </div>

    <!-- Tab 2: Performance Ranking -->
    <div id="tab-ranking" class="tab-content" style="display: none;">
        <?= $this->render('SunburstAnalytics:analytic/ranking_table', [
            'rankingData' => $rankingData,
            'project'     => $project,
            'fromDate'    => $fromDate,
            'toDate'      => $toDate,
        ]) ?>
    </div>
</div>

<div id="sunburst-app-config" 
     data-project-id="<?= (int)$project['id'] ?>"
     style="display:none;">
</div>

<?= $this->asset->css('plugins/SunburstAnalytics/assets/css/sunburst_workload.css') ?>
<?= $this->asset->js('plugins/SunburstAnalytics/assets/js/plotly.min.js') ?>
<?= $this->asset->js('plugins/SunburstAnalytics/assets/js/sunburst.js') ?>
