document.addEventListener("DOMContentLoaded", function () {
    const container = document.getElementById('sunburst-chart-container');
    const loadingElem = document.getElementById('chart-loading-msg');
    const dataHolder = document.getElementById('sunburst-data-holder');

    if (!container || !dataHolder) return;

    if (typeof Plotly === 'undefined') {
        container.innerHTML = '<div style="background:#fde8e8; color:#c81e1e; padding:20px; border-radius:8px; border:1px solid #f8b4b4; font-family:Tahoma; font-size:13px;">⚠️ فایل کتابخانه Plotly شناسایی نشد.</div>';
        return;
    }

    try {
        const rawData = JSON.parse(dataHolder.textContent || '{}');

        if (!rawData || !rawData.ids || rawData.ids.length <= 1) {
            container.innerHTML = '<div style="background:#fefcbf; color:#975a16; padding:20px; border-radius:8px; text-align:center; font-family:Tahoma;">هیچ تسک فعالی برای نمایش در پروژه یافت نشد!</div>';
            return;
        }

        const trace = {
            type: "sunburst",
            ids: rawData.ids,
            labels: rawData.labels,
            parents: rawData.parents,
            values: rawData.values,
            hovertext: rawData.hoverTexts,
            hoverinfo: "text",
            customdata: rawData.customData,
            outsidetextfont: { size: 12, color: "#2c3e50" },
            leaf: { opacity: 0.95 },
            marker: {
                colors: rawData.colors,
                colorscale: [
                    [0, '#e74c3c'],     // قرمز: ۰٪ پیشرفت
                    [0.5, '#f1c40f'],   // زرد: ۵۰٪ پیشرفت
                    [1.0, '#2ecc71']    // سبز: ۱۰۰٪ پیشرفت
                ],
                cmin: 0,
                cmax: 100,
                colorbar: {
                    title: 'پیشرفت %',
                    titleside: 'top',
                    ticksuffix: '%',
                    len: 0.55
                },
                line: { width: 1.5, color: '#ffffff' }
            }
        };

        const layout = {
            margin: { l: 15, r: 15, b: 15, t: 15 },
            sunburstcolorway: ["#3498db", "#9b59b6", "#34495e", "#1abc9c", "#e67e22", "#27ae60"],
            paper_bgcolor: 'rgba(0,0,0,0)',
            font: { family: "Tahoma, Segoe UI, sans-serif" }
        };

        const config = {
            responsive: true,
            displayModeBar: true,
            displaylogo: false,
            modeBarButtonsToRemove: ['lasso2d', 'select2d']
        };

        Plotly.newPlot(container, [trace], layout, config).then(function () {
            if (loadingElem) loadingElem.style.display = 'none';
        });

        // کلیک روی تسک برای باز شدن کارت مربوطه در برگه جدید
        container.on('plotly_sunburstclick', function (data) {
            if (!data.points || data.points.length === 0) return;
            const pt = data.points[0];
            const taskId = pt.customdata;
            const projectId = container.getAttribute('data-project-id');

            if (taskId && taskId > 0) {
                const taskUrl = '?controller=TaskViewController&action=show&task_id=' + taskId + '&project_id=' + projectId;
                window.open(taskUrl, '_blank');
                return false;
            }
        });

    } catch (err) {
        console.error("Plotly Sunburst Error:", err);
        container.innerHTML = '<div style="color:red; padding:20px; font-family:Tahoma;">خطا در رندر نمودار: ' + err.message + '</div>';
    }
});
