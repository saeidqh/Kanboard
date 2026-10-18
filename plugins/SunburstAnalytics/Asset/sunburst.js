document.addEventListener('DOMContentLoaded', function () {
    const dataContainer = document.getElementById('sunburst-data-holder');
    const plotDiv = document.getElementById('sunburst_chart');

    if (!dataContainer || !plotDiv) {
        return;
    }

    if (typeof Plotly === 'undefined') {
        plotDiv.innerHTML = '<div style="padding: 20px; color: #dc2626; text-align: center; font-family: Tahoma;">خطا: کتابخانه Plotly بارگذاری نشد. لطفاً مطمئن شوید فایل plotly.min.js در پوشه Asset وجود دارد.</div>';
        return;
    }

    let chartData;
    try {
        chartData = JSON.parse(dataContainer.textContent.trim());
    } catch (e) {
        console.error("Sunburst Error: خطا در پردازش داده‌ها:", e);
        plotDiv.innerHTML = '<div style="padding: 20px; color: #dc2626; text-align: center; font-family: Tahoma;">خطا در خواندن داده‌های نمودار.</div>';
        return;
    }

    if (!chartData || !chartData.ids || chartData.ids.length <= 1) {
        plotDiv.innerHTML = '<div style="padding: 30px; text-align: center; color: #64748b; font-family: Tahoma;">هیچ تسکی برای نمایش در این پروژه وجود ندارد.</div>';
        return;
    }

    const data = [{
        type: "sunburst",
        ids: chartData.ids,
        labels: chartData.labels,
        parents: chartData.parents,
        values: chartData.values,
        branchvalues: "remainder",
        customdata: chartData.customData,
        hovertext: chartData.hoverTexts,
        hoverinfo: "text",
        textinfo: "label",
        insidetextorientation: "radial",
        textfont: {
            family: "Tahoma, Segoe UI, sans-serif",
            size: 11,
            color: "#ffffff"
        },
        marker: {
            colors: chartData.colors,
            line: {
                color: "#ffffff",
                width: 1.5
            }
        },
        leaf: {
            opacity: 0.95
        }
    }];

    const layout = {
        margin: { l: 15, r: 15, b: 15, t: 15 },
        paper_bgcolor: "transparent",
        hoverlabel: {
            bgcolor: "#0f172a",
            bordercolor: "#38bdf8",
            font: {
                family: "Tahoma, Segoe UI, sans-serif",
                size: 13,
                color: "#f8fafc"
            },
            align: "right"
        }
    };

    const config = {
        responsive: true,
        displayModeBar: true,
        displaylogo: false,
        modeBarButtonsToRemove: ['sendDataToCloud', 'hoverClosestCartesian', 'hoverCompareCartesian', 'toggleHover', 'resetViews']
    };

    Plotly.newPlot(plotDiv, data, layout, config);

    // مدیریت کلیک برای هدایت مستقیم به تسک در برگه جدید
    plotDiv.on('plotly_click', function (eventData) {
        if (!eventData || !eventData.points || eventData.points.length === 0) return;
        const pt = eventData.points[0];
        const taskId = pt.customdata;
        if (taskId && taskId > 0) {
            const projectId = chartData.projectId || 1;
            const targetUrl = "?controller=TaskViewController&action=show&task_id=" + taskId + "&project_id=" + projectId;
            window.open(targetUrl, '_blank');
        }
    });
});
