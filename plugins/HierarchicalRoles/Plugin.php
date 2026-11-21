<?php

namespace Kanboard\Plugin\HierarchicalRoles;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Translator;
use Kanboard\Model\TaskModel;
use Kanboard\Plugin\HierarchicalRoles\Helper\HierarchicalRoleHelper;

class Plugin extends Base
{
    public function initialize()
    {
        // Register helper
        $this->helper->register('hierarchicalRoleHelper', '\Kanboard\Plugin\HierarchicalRoles\Helper\HierarchicalRoleHelper');

        // Override core model: assignable users + project user lists (search filters)
        $this->container['projectUserRoleModel'] = $this->container->factory(function ($c) {
            return new \Kanboard\Plugin\HierarchicalRoles\Model\FilteredProjectUserRoleModel($c);
        });

        // Template overrides: module access
        $this->template->setTemplateOverride('project_header/dropdown', 'HierarchicalRoles:project_header/dropdown');
        $this->template->setTemplateOverride('analytic/layout', 'HierarchicalRoles:analytic/layout');

        // Scope core analytics
        $this->container['userDistributionAnalytic'] = $this->container->factory(function ($c) {
            return new \Kanboard\Plugin\HierarchicalRoles\Analytic\ScopedUserDistributionAnalytic($c);
        });
        $this->container['taskDistributionAnalytic'] = $this->container->factory(function ($c) {
            return new \Kanboard\Plugin\HierarchicalRoles\Analytic\ScopedTaskDistributionAnalytic($c);
        });

        // ---- Board / list / dashboard visibility ----
        // Official Kanboard reference hook: alters query before board is rendered
        $container = $this->container;
        $this->hook->on('formatter:board:query', function (\PicoDb\Table &$query) use ($container) {
            $helper = new HierarchicalRoleHelper($container);
            // project_id is usually already constrained on the query; extract if possible
            $projectId = null;
            // Best-effort: from request when on board
            if (isset($container['request'])) {
                $projectId = (int) $container['request']->getIntegerParam('project_id', 0) ?: null;
            }
            $helper->applyBoardVisibilityToQuery($query, $projectId);
        });

        $this->hook->on('pagination:dashboard:task:query', function (\PicoDb\Table &$query) use ($container) {
            $helper = new HierarchicalRoleHelper($container);
            $helper->applyBoardVisibilityToQuery($query, null);
        });

        // Project menu entry
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
        return 'Organizational hierarchy for task assignment, board visibility, module access and analytics scope.';
    }

    public function getPluginAuthor()
    {
        return 'Ahvaz005 Team';
    }

    public function getPluginVersion()
    {
        return '1.4.1';
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
