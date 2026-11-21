<?php

namespace Kanboard\Plugin\RequestPlugin;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Translator;

class Plugin extends Base
{
    public function initialize()
    {
        // Routes
        $this->route->addRoute('/requests', 'RequestController', 'index', 'RequestPlugin');
        $this->route->addRoute('/requests/inbox', 'RequestController', 'inbox', 'RequestPlugin');
        $this->route->addRoute('/requests/sent', 'RequestController', 'sent', 'RequestPlugin');
        $this->route->addRoute('/requests/all', 'RequestController', 'all', 'RequestPlugin');
        $this->route->addRoute('/requests/create', 'RequestController', 'create', 'RequestPlugin');
        $this->route->addRoute('/requests/save', 'RequestController', 'save', 'RequestPlugin');
        $this->route->addRoute('/requests/show/:request_id', 'RequestController', 'show', 'RequestPlugin');
        $this->route->addRoute('/requests/accept/:request_id', 'RequestController', 'accept', 'RequestPlugin');
        $this->route->addRoute('/requests/reject/:request_id', 'RequestController', 'reject', 'RequestPlugin');
        $this->route->addRoute('/requests/forward/:request_id', 'RequestController', 'forward', 'RequestPlugin');
        $this->route->addRoute('/requests/comment/:request_id', 'RequestController', 'comment', 'RequestPlugin');
        $this->route->addRoute('/requests/convert/:request_id', 'RequestController', 'convert', 'RequestPlugin');
        $this->route->addRoute('/requests/users/:project_id', 'RequestController', 'users', 'RequestPlugin');
        $this->route->addRoute('/requests/complete/:request_id', 'RequestController', 'complete', 'RequestPlugin');

        // Sidebar menu
        $this->template->hook->attach('template:project-list:menu:after', 'RequestPlugin:layout/menu');
        $this->template->hook->attach('template:dashboard:sidebar', 'RequestPlugin:layout/menu');
        $this->template->hook->attach('template:layout:main-menu', 'RequestPlugin:layout/menu');

        // Header quick button
        $this->template->hook->attach('template:header:dropdown', 'RequestPlugin:layout/header_dropdown');

        // CSS / JS
        $this->hook->on('template:layout:css', array('template' => 'plugins/RequestPlugin/Assets/css/request.css'));
        $this->hook->on('template:layout:js', array('template' => 'plugins/RequestPlugin/Assets/js/request.js'));
    }

    public function onStartup()
    {
        Translator::load($this->languageModel->getCurrentLanguage(), __DIR__ . '/Locale');
    }

    public function getClasses()
    {
        return array(
            'Plugin\RequestPlugin\Model' => array(
                'RequestModel',
                'RequestCommentModel',
                'RequestHistoryModel',
            ),
        );
    }


    public function getApplicationAccess()
    {
        return array(
            'RequestController' => 'app-user',
        );
    }

    public function getPluginName()
    {
        return 'Request';
    }

    public function getPluginDescription()
    {
        return 'Request module: send requests to anyone in any project, inbox/sent/all, forward, convert to task';
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

    public function getPluginHomepage()
    {
        return '';
    }
}
