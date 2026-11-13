<?php

namespace Kanboard\Plugin\HierarchicalRoles;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Translator;

class Plugin extends Base
{
    public function initialize()
    {
        // Register helper
        $this->helper->register('hierarchicalRoleHelper', '\Kanboard\Plugin\HierarchicalRoles\Helper\HierarchicalRoleHelper');

        // Override core model so getAssignableUsers() is filtered
        // (Kanboard core has NO hook inside getAssignableUsers)
        $this->container['projectUserRoleModel'] = $this->container->factory(function ($c) {
            return new \Kanboard\Plugin\HierarchicalRoles\Model\FilteredProjectUserRoleModel($c);
        });

        // Override core templates to enforce module access + keep core UI
        $this->template->setTemplateOverride('project_header/dropdown', 'HierarchicalRoles:project_header/dropdown');
        $this->template->setTemplateOverride('analytic/layout', 'HierarchicalRoles:analytic/layout');

        // Project menu entry (only visible if user has module access)
        $this->template->hook->attach('template:project:dropdown', 'HierarchicalRoles:project/dropdown');
        $this->template->hook->attach('template:project-header:view-switcher', 'HierarchicalRoles:project/header');
        $this->template->hook->attach('template:project:sidebar', 'HierarchicalRoles:project/sidebar');
    }

    public function onStartup()
    {
        Translator::load($this->languageModel->getCurrentLanguage(), __DIR__ . '/Locale');
    }

    public function getPluginName()
    {
        return 'Hierarchical Roles';
    }

    public function getPluginDescription()
    {
        return 'Organizational hierarchy for task assignment. Users can only assign tasks to their subordinates. Role-based access to the management module.';
    }

    public function getPluginAuthor()
    {
        return 'Ahvaz005 Team';
    }

    public function getPluginVersion()
    {
        return '1.2.1';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/saeidqh/Kanboard';
    }

    public function getCompatibleVersion()
    {
        return '>=1.2.20';
    }
}
