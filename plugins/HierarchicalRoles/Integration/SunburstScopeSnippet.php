<?php
/**
 * Copy methods into WorkloadAnalysisService (SunburstAnalytics).
 * Requires HierarchicalRoles plugin enabled.
 *
 * Usage before fetching tasks:
 *   $query = $this->applyOwnerScope($query, $projectId);
 */

/*
protected function getScopeUserIds($projectId)
{
    if (!class_exists('\\Kanboard\\Plugin\\HierarchicalRoles\\Helper\\HierarchicalRoleHelper')) {
        return null;
    }
    $helper = new \\Kanboard\\Plugin\\HierarchicalRoles\\Helper\\HierarchicalRoleHelper($this->container);
    return $helper->getAnalyticsVisibleUserIds($projectId);
}

protected function applyOwnerScope($query, $projectId)
{
    $scopeIds = $this->getScopeUserIds($projectId);
    if ($scopeIds === null) {
        return $query;
    }
    if (empty($scopeIds)) {
        return $query->eq('owner_id', -1);
    }
    return $query->in('owner_id', $scopeIds);
}
*/
