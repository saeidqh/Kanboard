<?php

namespace Kanboard\Plugin\ModernUI;

use Kanboard\Core\Plugin\Base;

class Plugin extends Base
{
    public function initialize()
    {
        // تزریق فایل استایل اصلی پوسته به تمامی صفحات
        $this->hook->on('template:layout:css', array('template' => 'plugins/ModernUI/Assets/theme.css'));
    }

    public function getPluginName()
    {
        return 'Modern Executive UI';
    }

    public function getPluginDescription()
    {
        return 'Ultra-modern, clean and professional design for engineering projects.';
    }

    public function getPluginAuthor()
    {
        return 'Ahvaz005 Team';
    }

    public function getPluginVersion()
    {
        return '1.0.0';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/kanboard/kanboard';
    }
}
