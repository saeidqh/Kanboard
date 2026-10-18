document.addEventListener('DOMContentLoaded', function () {
    const dataContainer = document.getElementById('sunburst-data-holder');
    const chartDom = document.getElementById('echarts_sunburst');

    if (!dataContainer || !chartDom) return;

    if (typeof echarts === 'undefined') {
        chartDom.innerHTML = '<div style="padding: 30px; color: #ef4444; text-align: center; font-family: Tahoma; font-size: 14px; font-weight: bold;">فایل echarts.min.js در مسیر لوکال یافت نشد. لطفاً مطمئن شوید فایل در plugins/SunburstAnalytics/Asset/ قرار دارد.</div>';
        return;
    }

    let rawData;
    try {
        rawData = JSON.parse(dataContainer.textContent.trim());
    } catch (e) {
        console.error("Error parsing JSON:", e);
        return;
    }

    if (!rawData || !rawData.ids || rawData.ids.length <= 1) {
        chartDom.innerHTML = '<div style="padding: 30px; text-align: center; color: #64748b; font-family: Tahoma;">هیچ داده‌ای برای نمایش یافت نشد.</div>';
        return;
    }

    // ساختار سلسله‌مراتبی درختی برای Apache ECharts
    let treeRoot = {
        name: rawData.labels[0],
        itemStyle: { color: rawData.colors[0] },
        tooltipText: rawData.hoverTexts[0],
        children: []
    };

    let nodeMap = {};
    nodeMap['root'] = treeRoot;

    for (let i = 1; i < rawData.ids.length; i++) {
        let id = rawData.ids[i];
        let parentId = rawData.parents[i];
        let label = rawData.labels[i];
        let color = rawData.colors[i];
        let hover = rawData.hoverTexts[i];
        let taskId = rawData.customData[i];

        let node = {
            name: label,
            itemStyle: { color: color },
            tooltipText: hover,
            taskId: taskId,
            children: []
        };

        // تسک‌های لایه نهایی وزن ۱ می‌گیرند تا دایره کاملاً ۳۶۰ درجه و بالانس شود
        if (taskId && taskId > 0) {
            node.value = 1;
        }

        nodeMap[id] = node;

        if (nodeMap[parentId]) {
            nodeMap[parentId].children.push(node);
        }
    }

    // مقداردهی اولیه اینستنس
    const myChart = echarts.init(chartDom);

    const option = {
        backgroundColor: 'transparent',
        tooltip: {
            trigger: 'item',
            confine: true,
            backgroundColor: 'rgba(15, 23, 42, 0.92)',
            borderColor: '#38bdf8',
            borderWidth: 1.5,
            padding: [10, 14],
            textStyle: {
                color: '#f8fafc',
                fontFamily: 'Tahoma, Segoe UI, sans-serif',
                fontSize: 12.5,
                lineHeight: 20
            },
            formatter: function (params) {
                return params.data.tooltipText || params.name;
            }
        },
        series: [{
            type: 'sunburst',
            data: [treeRoot],
            radius: ['14%', '92%'],
            sort: undefined, // حفظ ترتیب ستون‌ها
            nodeClick: 'rootToNode', // با کلیک روی هر بخش وارد آن لایه می‌شود
            itemStyle: {
                borderRadius: 4,
                borderWidth: 1.8,
                borderColor: '#ffffff'
            },
            label: {
                rotate: 'radial',
                fontFamily: 'Tahoma, Segoe UI, sans-serif',
                fontSize: 11,
                color: '#ffffff',
                fontWeight: '600'
            },
            emphasis: {
                focus: 'ancestor'
            },
            levels: [
                {},
                // لایه ۱: نام پروژه
                {
                    r0: '14%',
                    r: '30%',
                    label: { rotate: 0, fontSize: 13, fontWeight: 'bold' }
                },
                // لایه ۲: کارشناسان پروژه
                {
                    r0: '30%',
                    r: '52%',
                    label: { rotate: 'radial', fontSize: 11 }
                },
                // لایه ۳: ستون‌های کاری
                {
                    r0: '52%',
                    r: '72%',
                    label: { rotate: 'tangential', fontSize: 11 }
                },
                // لایه ۴: تسک‌ها
                {
                    r0: '72%',
                    r: '92%',
                    label: { position: 'outside', padding: 3, silent: false, fontSize: 10 }
                }
            ]
        }]
    };

    myChart.setOption(option);

    // هدایت به صفحه تسک با کلیک بر روی لایه بیرونی
    myChart.on('click', function (params) {
        if (params.data && params.data.taskId && params.data.taskId > 0) {
            const taskId = params.data.taskId;
            const projectId = rawData.projectId || 1;
            const targetUrl = "?controller=TaskViewController&action=show&task_id=" + taskId + "&project_id=" + projectId;
            window.open(targetUrl, '_blank');
        }
    });

    // تطبیق ابعاد هنگام تغییر سایز مرورگر
    window.addEventListener('resize', function () {
        myChart.resize();
    });
});
