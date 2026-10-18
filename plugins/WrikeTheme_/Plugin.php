<?php

namespace Kanboard\Plugin\WrikeTheme;

use Kanboard\Core\Plugin\Base;

class Plugin extends Base
{
    public function initialize()
    {
        global $themeWrikeConfig;

        // ------------------------------------------------------------------
        // Config: load from data/files/ (user-editable) or copy defaults
        // ------------------------------------------------------------------
        $configPath = DATA_DIR . '/files/WrikeTheme/config.php';

        if (file_exists($configPath)) {
            require_once($configPath);
        } else {
            mkdir(DATA_DIR . '/files/WrikeTheme/Assets/images', 0755, true);
            copy('plugins/WrikeTheme/config.php', $configPath);
            copy(
                'plugins/WrikeTheme/Assets/images/brand-logo.png',
                DATA_DIR . '/files/WrikeTheme/Assets/images/brand-logo.png'
            );
        }

        // DB settings override the file config when present
        $dbLogo    = $this->configModel->get('wriketheme_logo', '');
        $dbHeader  = $this->configModel->get('wriketheme_header_color', '');
        $dbTitle   = $this->configModel->get('wriketheme_title_color', '');

        if (!empty($dbLogo))   { $themeWrikeConfig['logo']                  = $dbLogo; }
        if (!empty($dbHeader)) { $themeWrikeConfig['backgroundColorHeader']  = $dbHeader; }
        if (!empty($dbTitle))  { $themeWrikeConfig['headingTitleColor']      = $dbTitle; }

        // ------------------------------------------------------------------
        // Asset hooks — CSS & JS
        // ------------------------------------------------------------------
        $this->hook->on('template:layout:css',
            ['template' => 'plugins/WrikeTheme/Assets/css/wrike.css']);
        $this->hook->on('template:layout:css',
            ['template' => 'plugins/WrikeTheme/Assets/css/prism.css']);
        $this->hook->on('template:layout:js',
            ['template' => 'plugins/WrikeTheme/Assets/js/clipboard.min.js']);
        $this->hook->on('template:layout:js',
            ['template' => 'plugins/WrikeTheme/Assets/js/prism.js']);
        $this->hook->on('template:layout:js',
            ['template' => 'plugins/WrikeTheme/Assets/js/wrike.js']);

        // ------------------------------------------------------------------
        // Template overrides — layout, header, title
        // ------------------------------------------------------------------
        $this->template->setTemplateOverride('header/title', 'WrikeTheme:layout/header/title');
        $this->template->setTemplateOverride('header', 'WrikeTheme:header');
        $this->template->setTemplateOverride('layout', 'WrikeTheme:layout');

        // ------------------------------------------------------------------
        // Settings page — sidebar link + routes
        // ------------------------------------------------------------------
        $this->template->hook->attach('template:config:sidebar', 'WrikeTheme:config/sidebar');

        $this->route->addRoute(
            '/settings/wrike-theme',
            'WrikeThemeController',
            'show',
            'WrikeTheme'
        );
        $this->route->addRoute(
            '/settings/wrike-theme/save',
            'WrikeThemeController',
            'save',
            'WrikeTheme'
        );

        // ------------------------------------------------------------------
        // Night mode toggle — AJAX endpoint (persists in userMetadataModel)
        // ------------------------------------------------------------------
        $this->route->addRoute(
            '/wrike-theme/toggle-night',
            'WrikeThemeController',
            'toggleNight',
            'WrikeTheme'
        );

        // ------------------------------------------------------------------
        // Login page branding
        // ------------------------------------------------------------------
        $this->template->hook->attach(
            'template:auth:login-form:before',
            'WrikeTheme:auth/login_header'
        );

        // ------------------------------------------------------------------
        // White color added to Default Task Color dropdown
        // ------------------------------------------------------------------
        $this->hook->on('model:color:get-list', function (&$listing) {
            if (!isset($listing['white'])) {
                $listing['white'] = 'White';
            }
        });

        // ------------------------------------------------------------------
        // Default task color — apply the color configured in settings
        // ------------------------------------------------------------------
        $this->hook->on('controller:task:form:default', function (array $default_values) {
            $defaultColor = $this->configModel->get('wriketheme_default_task_color', '');
            if (!empty($defaultColor) && empty($default_values['color_id'])) {
                return ['color_id' => $defaultColor];
            }
            return [];
        });
    }

    public function getClasses()
    {
        return [
            'Plugin\WrikeTheme\Controller' => ['WrikeThemeController'],
        ];
    }

    public function getPluginName()
    {
        return 'WrikeTheme';
    }

    public function getPluginDescription()
    {
        return 'Modern Wrike-inspired theme for Kanboard';
    }

    public function getPluginAuthor()
    {
        return 'User';
    }

    public function getPluginVersion()
    {
        return '1.1.3';
    }

    public function getCompatibleVersion()
    {
        return '>=1.0.48';
    }

    public function getPluginHomepage()
    {
        return '';
    }
}
