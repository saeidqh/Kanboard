<?php

namespace Kanboard\Plugin\TaskActivitySidebar;

use Kanboard\Core\Plugin\Base;

class Plugin extends Base
{
    public function initialize()
    {
        $this->template->hook->attach('template:task:show:bottom', 'TaskActivitySidebar:task/sidebar');
        $this->hook->on('template:layout:css', array(
            'template' => 'plugins/TaskActivitySidebar/Assets/css/sidebar.css',
        ));
    }

    public function getPluginName()
    {
        return 'Task Activity Sidebar';
    }

    public function getPluginDescription()
    {
        return 'Show the native task activity stream in a right-hand sidebar on the task page.';
    }

    public function getPluginAuthor()
    {
        return 'Custom';
    }

    public function getPluginVersion()
    {
        return '1.0.0';
    }

    public function getCompatibleVersion()
    {
        return '>=1.2.0';
    }
}
