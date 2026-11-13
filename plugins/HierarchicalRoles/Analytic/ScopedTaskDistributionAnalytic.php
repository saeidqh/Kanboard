<?php

namespace Kanboard\Plugin\HierarchicalRoles\Analytic;

use Kanboard\Analytic\TaskDistributionAnalytic;
use Kanboard\Model\TaskModel;
use Kanboard\Plugin\HierarchicalRoles\Helper\HierarchicalRoleHelper;

/**
 * Task distribution by column: only tasks owned by users in scope.
 * Always includes "percentage" key expected by core template.
 */
class ScopedTaskDistributionAnalytic extends TaskDistributionAnalytic
{
    public function build($project_id)
    {
        $helper = new HierarchicalRoleHelper($this->container);
        $visible = $helper->getAnalyticsVisibleUserIds($project_id);

        // Full project → core behaviour (includes percentage)
        if ($visible === null) {
            return parent::build($project_id);
        }

        if (empty($visible)) {
            return array();
        }

        $columns = $this->columnModel->getAll($project_id);
        $metrics = array();
        $total = 0;

        foreach ($columns as $column) {
            $nb = $this->db->table(TaskModel::TABLE)
                ->eq('project_id', $project_id)
                ->eq('column_id', $column['id'])
                ->eq('is_active', TaskModel::STATUS_OPEN)
                ->in('owner_id', $visible)
                ->count();

            $total += $nb;

            $metrics[] = array(
                'column_title' => $column['title'],
                'nb_tasks'     => $nb,
            );
        }

        if ($total === 0) {
            return array();
        }

        foreach ($metrics as &$metric) {
            $metric['percentage'] = round(($metric['nb_tasks'] * 100) / $total, 2);
        }
        unset($metric);

        return $metrics;
    }
}
