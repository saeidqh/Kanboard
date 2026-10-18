<?php

namespace Kanboard\Plugin\SunburstAnalytics;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Translator;

class Plugin extends Base
{
    public function initialize()
    {
        // اضافه کردن لینک جدید به منوی سایدبار بخش Analytics
        $this->template->hook->attach('template:analytic:sidebar', 'SunburstAnalytics:analytic/sidebar');
    }

    public function onStartup()
    {
        Translator::load($this->languageModel->getCurrentLanguage(), __DIR__.'/Locale');
    }

    public function getPluginName()
    {
        return 'Sunburst Analytics';
    }

    public function getPluginDescription()
    {
        return 'Interactive Multi-layer Sunburst Chart for Team Workload & Task Progress';
    }

    public function getPluginAuthor()
    {
        return 'Ahvaz005 Project Team';
    }

    public function getPluginVersion()
    {
        return '1.0.0';
    }

    public function getPluginHomepage()
    {
        return '';
    }
}
