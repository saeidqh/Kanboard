<?php

namespace Kanboard\Plugin\HierarchicalRoles\Analytic;

use Kanboard\Analytic\UserDistributionAnalytic;
use Kanboard\Plugin\HierarchicalRoles\Helper\HierarchicalRoleHelper;

/**
 * User repartition pie: only users inside current hierarchy scope.
 */
class ScopedUserDistributionAnalytic extends UserDistributionAnalytic
{
    public function build($project_id)
    {
        $metrics = parent::build($project_id);

        $helper = new HierarchicalRoleHelper($this->container);
        $visible = $helper->getAnalyticsVisibleUserIds($project_id);

        if ($visible === null) {
            return $metrics;
        }

        $set = array_flip($visible);
        $filtered = array();

        foreach ($metrics as $row) {
            // Core returns user_id / nb_tasks style rows
            $uid = isset($row['user_id']) ? (int) $row['user_id'] : (isset($row['id']) ? (int) $row['id'] : 0);
            if ($uid > 0 && isset($set[$uid])) {
                $filtered[] = $row;
            }
        }

        return $filtered;
    }
}
