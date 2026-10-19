document.addEventListener("DOMContentLoaded", function () {
    var dataContainer = document.getElementById("sunburst-data-holder");
    var plotDiv = document.getElementById("sunburst_chart");
    var loader = document.getElementById("chart-loading");

    if (!dataContainer || !plotDiv) {
        return;
    }

    if (typeof Plotly === "undefined") {
        if (loader) loader.style.display = "none";
        plotDiv.innerHTML = '<div class="alert alert-error">Plotly library not loaded! Check assets/js/plotly.min.js</div>';
        return;
    }

    try {
        var rawJson = dataContainer.textContent.trim();
        var chartData = JSON.parse(rawJson);

        if (!chartData.ids || chartData.ids.length <= 1) {
            if (loader) loader.style.display = "none";
            plotDiv.innerHTML = '<p class="alert alert-info">No tasks available to display in this project.</p>';
            return;
        }

        var trace = {
            type: "sunburst",
            ids: chartData.ids,
            labels: chartData.labels,
            parents: chartData.parents,
            values: chartData.values,
            branchvalues: "total",
            hovertext: chartData.hoverTexts,
            hoverinfo: "text",
            customdata: chartData.customData,
            textinfo: "label",
            insidetextorientation: "auto", // Automatically aligns text horizontally or radially for maximum clarity
            insidetextfont: {
                size: 13,
                family: "Segoe UI, Tahoma, sans-serif"
            },
            marker: {
                colors: chartData.colors,
                line: { color: "#ffffff", width: 1.5 }
            },
            leaf: { opacity: 0.95 }
        };

        var layout = {
            height: 850, // Extra large height for superior visibility
            margin: { l: 0, r: 0, b: 0, t: 0 }, // Zero margin to use 100% of the canvas
            paper_bgcolor: "transparent",
            plot_bgcolor: "transparent",
            font: { family: "Segoe UI, Tahoma, sans-serif", size: 12 },
            hoverlabel: {
                bgcolor: "#1e293b",
                bordercolor: "#334155",
                font: { color: "#ffffff", family: "Segoe UI, Tahoma, sans-serif", size: 13 }
            }
        };

        var config = {
            responsive: true,
            displayModeBar: false
        };

        Plotly.newPlot(plotDiv, [trace], layout, config).then(function () {
            if (loader) loader.style.display = "none";
        });

        // Responsive resize
        window.addEventListener('resize', function () {
            Plotly.relayout(plotDiv, { width: plotDiv.clientWidth });
        });

        // Click to drill-down or open task in new tab
        plotDiv.on('plotly_click', function (data) {
            if (data.points && data.points.length > 0) {
                var point = data.points[0];
                var taskId = point.customdata;
                var projectId = chartData.projectId || 1;

                if (taskId && taskId > 0) {
                    var taskUrl = '?controller=TaskViewController&action=show&task_id=' + taskId + '&project_id=' + projectId;
                    window.open(taskUrl, '_blank');
                }
            }
        });

    } catch (e) {
        console.error("Sunburst rendering error:", e);
        if (loader) loader.style.display = "none";
        plotDiv.innerHTML = '<div class="alert alert-error">Sunburst rendering error: ' + e.message + '</div>';
    }
});

// Accordion delegation for user rows
document.addEventListener("click", function (e) {
    var btn = e.target.closest(".btn-toggle-columns");
    if (!btn) return;

    var rowId = btn.getAttribute("data-target");
    var row = document.getElementById(rowId);
    if (!row) return;

    var icon = btn.querySelector("i");

    if (row.style.display === "none" || row.style.display === "") {
        row.style.display = "table-row";
        if (icon) icon.className = "fa fa-minus";
        btn.classList.add("active");
    } else {
        row.style.display = "none";
        if (icon) icon.className = "fa fa-plus";
        btn.classList.remove("active");
    }
});
// ========================================
// Tab Switching Logic (CSP-safe)
// ========================================
document.addEventListener("DOMContentLoaded", function () {
    // خواندن projectId از DOM
    var configEl = document.getElementById("sunburst-app-config");
    if (configEl) {
        window.sunburstProjectId = parseInt(configEl.getAttribute("data-project-id")) || 1;
    }

    // اتصال دکمه‌های تب به تابع switchAnalyticsTab
    var tabButtons = document.querySelectorAll(".analytics-tabs-nav .tab-btn");
    tabButtons.forEach(function (btn) {
        btn.addEventListener("click", function () {
            var targetTab = btn.getAttribute("data-tab");
            if (targetTab) {
                switchAnalyticsTab(targetTab);
            }
        });
    });
});

function switchAnalyticsTab(tabName) {
    var workloadTab = document.getElementById("tab-workload");
    var rankingTab = document.getElementById("tab-ranking");
    var buttons = document.querySelectorAll(".analytics-tabs-nav .tab-btn");

    if (!workloadTab || !rankingTab) {
        console.error("Analytics tab containers not found!");
        return;
    }

    buttons.forEach(function (btn) {
        btn.classList.remove("active");
    });

    if (tabName === "workload") {
        workloadTab.style.display = "block";
        rankingTab.style.display = "none";
        if (buttons[0]) buttons[0].classList.add("active");

        // Resize Plotly after tab becomes visible
        var chartElem = document.getElementById("sunburst_chart");
        if (window.Plotly && chartElem) {
            setTimeout(function () {
                window.Plotly.Plots.resize(chartElem);
            }, 50);
        }
    } else {
        workloadTab.style.display = "none";
        rankingTab.style.display = "block";
        if (buttons[1]) buttons[1].classList.add("active");
    }
}