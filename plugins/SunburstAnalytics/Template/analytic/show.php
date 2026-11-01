<div class="page-header" style="text-align: left; direction: ltr;">
    <h2><?= $this->text->e($title) ?></h2>
</div>

<div class="sunburst-analytics-wrapper" style="text-align: left; direction: ltr;">

    <!-- Navigation Tabs -->
    <div class="analytics-tabs-nav">
        <button
            type="button"
            class="tab-btn active"
            data-tab="workload"
        >
            <i class="fa fa-pie-chart" aria-hidden="true"></i>
            Workload & Sunburst
        </button>

        <button
            type="button"
            class="tab-btn"
            data-tab="ranking"
        >
            <i class="fa fa-trophy" aria-hidden="true"></i>
            Performance Ranking
        </button>
    </div>

    <!-- Tab 1: Workload & Sunburst -->
    <div id="tab-workload" class="tab-content active">

        <div class="sunburst-chart-box">
            <div id="chart-loading" class="chart-loading-overlay">
                <div class="spinner"></div>
                <p>Generating Sunburst Workload Chart...</p>
            </div>

            <div
                id="sunburst_chart"
                style="width: 100%; height: 850px;"
            ></div>
        </div>

        <!-- Sunburst chart data -->
        <script id="sunburst-data-holder" type="application/json"><?= $chartData ?></script>

        <!-- Workload table -->
        <div class="sunburst-table-box" style="margin-top: 30px;">
            <?= $this->render('SunburstAnalytics:analytic/workload_table', [
                'workloadData' => $workloadData,
                'project'      => $project,
            ]) ?>
        </div>

    </div>

    <!-- Tab 2: Performance Ranking -->
    <div
        id="tab-ranking"
        class="tab-content"
        style="display: none;"
    >
        <?= $this->render('SunburstAnalytics:analytic/ranking_table', [
            'rankingData' => $rankingData,
            'project'     => $project,
            'fromDate'    => $fromDate,
            'toDate'      => $toDate,
        ]) ?>
    </div>

</div>

<!-- Project configuration -->
<div
    id="sunburst-app-config"
    data-project-id="<?= (int) $project['id'] ?>"
    style="display: none;"
></div>

<!-- CSS and JavaScript assets -->
 <?= $this->asset->js('plugins/PersianBoard/Assets/js/persian-date.min.js') ?>
<?= $this->asset->js('plugins/PersianBoard/Assets/js/persian-datepicker.min.js') ?>
<?= $this->asset->js('plugins/PersianBoard/Assets/js/PersianBoard.js') ?>
<?= $this->asset->css('plugins/SunburstAnalytics/assets/css/sunburst_workload.css') ?>

<?= $this->asset->js('plugins/SunburstAnalytics/assets/js/plotly.min.js') ?>

<?= $this->asset->js('plugins/SunburstAnalytics/assets/js/sunburst.js') ?>


<!-- Persian date picker: Performance Ranking only -->
<script>
$(document).ready(function () {

    /**
     * Convert Jalali YYYY/MM/DD to Gregorian YYYY-MM-DD.
     * Uses the existing persian-date library.
     *
     * Returns:
     * - Gregorian date string when valid
     * - Empty string when the input is empty
     * - null when the input is invalid
     */
    function jalaliToGregorian(value) {
        value = (value || '').trim();

        if (value === '') {
            return '';
        }

        var parts = value.split('/');

        if (parts.length !== 3) {
            return null;
        }

        if (!/^\d{4}\/\d{1,2}\/\d{1,2}$/.test(value)) {
            return null;
        }

        var jy = parseInt(parts[0], 10);
        var jm = parseInt(parts[1], 10);
        var jd = parseInt(parts[2], 10);

        if (
            isNaN(jy) ||
            isNaN(jm) ||
            isNaN(jd) ||
            jm < 1 ||
            jm > 12 ||
            jd < 1 ||
            jd > 31
        ) {
            return null;
        }

        try {
            var gregorian = new persianDate([jy, jm, jd])
                .toLocale('en')
                .toCalendar('gregorian')
                .format('YYYY-MM-DD');

            // Verify the generated Gregorian date.
            var dateParts = gregorian.split('-');

            if (dateParts.length !== 3) {
                return null;
            }

            var gy = parseInt(dateParts[0], 10);
            var gm = parseInt(dateParts[1], 10);
            var gd = parseInt(dateParts[2], 10);

            var checkDate = new Date(Date.UTC(gy, gm - 1, gd));

            if (
                checkDate.getUTCFullYear() !== gy ||
                checkDate.getUTCMonth() + 1 !== gm ||
                checkDate.getUTCDate() !== gd
            ) {
                return null;
            }

            return gregorian;

        } catch (error) {
            return null;
        }
    }


    /**
     * Convert Gregorian YYYY-MM-DD to Jalali YYYY/MM/DD.
     */
    function gregorianToJalali(value) {
        value = (value || '').trim();

        if (!value) {
            return '';
        }

        if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
            return value;
        }

        var parts = value.split('-');

        var gy = parseInt(parts[0], 10);
        var gm = parseInt(parts[1], 10);
        var gd = parseInt(parts[2], 10);

        var checkDate = new Date(Date.UTC(gy, gm - 1, gd));

        if (
            checkDate.getUTCFullYear() !== gy ||
            checkDate.getUTCMonth() + 1 !== gm ||
            checkDate.getUTCDate() !== gd
        ) {
            return '';
        }

        try {
            var date = new Date(gy, gm - 1, gd, 12, 0, 0);

            return new persianDate(date).format('YYYY/MM/DD');

        } catch (error) {
            return '';
        }
    }


    /**
     * Initialize a date picker and synchronize its hidden field.
     */
    function setupDatePicker(displayId, hiddenId) {
        var $display = $('#' + displayId);
        var $hidden = $('#' + hiddenId);

        if (!$display.length || !$hidden.length) {
            return;
        }

        // Convert the controller's Gregorian initial value to Jalali.
        var initialValue = $hidden.val();

        if (initialValue && initialValue.indexOf('-') !== -1) {
            $display.val(gregorianToJalali(initialValue));
        }

        // Avoid initializing the same date picker more than once.
        if ($display.data('pDatepicker')) {
            return;
        }

        $display.pDatepicker({
            format: 'YYYY/MM/DD',
            autoClose: true,
            initialValue: false,

            onSelect: function (unix) {
                var selectedJalali = new persianDate(unix)
                    .format('YYYY/MM/DD');

                $display.val(selectedJalali);

                var selectedGregorian = jalaliToGregorian(
                    selectedJalali
                );

                if (selectedGregorian !== null) {
                    $hidden.val(selectedGregorian);
                }
            }
        });

        // Support manually typed dates as well.
        $display.on('change', function () {
            var gregorianValue = jalaliToGregorian(
                $display.val()
            );

            if (gregorianValue !== null) {
                $hidden.val(gregorianValue);
            }
        });
    }


    // Initialize only the Performance Ranking date fields.
    setupDatePicker('from_date_display', 'from_date');
    setupDatePicker('to_date_display', 'to_date');


    /**
     * Synchronize both fields before submitting the filter form.
     */
    $('.ranking-filter-form').on('submit', function (event) {

        var fromValue = $('#from_date_display').val();
        var toValue = $('#to_date_display').val();

        var fromGregorian = jalaliToGregorian(fromValue);
        var toGregorian = jalaliToGregorian(toValue);

        if (fromGregorian === null) {
            event.preventDefault();

            alert(
                'Please enter a valid From Date in YYYY/MM/DD format.'
            );

            return;
        }

        if (toGregorian === null) {
            event.preventDefault();

            alert(
                'Please enter a valid To Date in YYYY/MM/DD format.'
            );

            return;
        }

        // These are the only date values submitted to PHP.
        $('#from_date').val(fromGregorian);
        $('#to_date').val(toGregorian);
    });

});
</script>