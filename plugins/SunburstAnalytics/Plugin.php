<?php

namespace Kanboard\Plugin\SunburstAnalytics;

use Kanboard\Core\Plugin\Base;
use Kanboard\Plugin\SunburstAnalytics\Service\WorkloadAnalysisService;

class Plugin extends Base
{
    public function initialize()
    {
        // رجیستر کردن سرویس به عنوان یک کامپوننت در کانتینر
        $this->container['workloadAnalysisService'] = function ($c) {
            return new WorkloadAnalysisService($c);
        };

        // افزودن منوی سمت چپ به صفحه Analytics
        $this->template->hook->attach('template:analytic:sidebar', 'SunburstAnalytics:analytic/sidebar');

        // بارگذاری استایل اختصاصی
$this->hook->on('template:layout:css', array('template' => 'plugins/SunburstAnalytics/assets/css/sunburst_workload.css'));
    }

    public function getPluginName()
    {
        return 'SunburstAnalytics';
    }

    public function getPluginDescription()
    {
        return 'Interactive Sunburst Chart & Workload Delay Monitor';
    }

    public function getPluginAuthor()
    {
        return 'Saeid Hosseini';
    }

    public function getPluginVersion()
    {
        return '1.1.0';
    }
}
