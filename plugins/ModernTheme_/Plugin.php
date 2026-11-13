<?php

namespace Kanboard\Plugin\ModernTheme;

use Kanboard\Core\Plugin\Base;

class Plugin extends Base
{
    public function initialize()
    {
        // بارگذاری فایل‌های CSS به ترتیب درست (از متغیرها تا کامپوننت‌ها)
        $this->hook->on('template:layout:css', array(
            'template' => 'plugins/ModernTheme/Assets/css/variables.css'
        ));
        $this->hook->on('template:layout:css', array(
            'template' => 'plugins/ModernTheme/Assets/css/base.css'
        ));
        $this->hook->on('template:layout:css', array(
            'template' => 'plugins/ModernTheme/Assets/css/header.css'
        ));
        $this->hook->on('template:layout:css', array(
            'template' => 'plugins/ModernTheme/Assets/css/board.css'
        ));
        $this->hook->on('template:layout:css', array(
            'template' => 'plugins/ModernTheme/Assets/css/cards.css'
        ));
    }

    public function getPluginName()
    {
        return 'Modern Theme';
    }

    public function getPluginDescription()
    {
        return 'تم مدرن و حرفه‌ای برای کان‌بورد — نوبار جدا، آبی خوش‌رنگ، گوشه‌های گرد و تفکیک واضح منوها';
    }

    public function getPluginAuthor()
    {
        return 'Saeidqh';
    }

    public function getPluginVersion()
    {
        return '2.1.0';
    }

    public function getCompatibleVersion()
    {
        return '>=1.2.0';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/saeidqh/Kanboard';
    }
}
